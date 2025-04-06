<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use IEXBase\TronAPI\Tron;
use IEXBase\TronAPI\Provider\HttpProvider;
use Illuminate\Support\Facades\Session;

class CommissionController extends Controller
{
    protected $apiClient;
    protected $tron;
    protected $node;

    public function __construct()
    {
        $this->apiClient = new Client([
            'base_uri' => config('services.api_url'),
            'timeout'  => 2.0,
        ]);

        $node = new HttpProvider(env('TRON_API'));
        $this->tron = new Tron(
            $node, $node, $node,null,null,
            config('services.tron.private_key')
        );
    }

    public function index()
    {
        try {
            $response = $this->apiClient->get('/api/commissions/list');
            $data = json_decode($response->getBody(), true);

            return view('commissions', ['commissions' => $data['result']]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function pay(Request $request, $commissionId)
    {
        try {
            // Получаем полный список комиссий
            $response = $this->apiClient->get('/api/commissions/list');
            $data = json_decode($response->getBody(), true);

            // Ищем нужную комиссию
            $commission = collect($data['result'])->firstWhere('id', $commissionId);

            if (!$commission) {
                throw new \Exception('Не найден счет');
            }

            // Проверяем баланс TRX
            $balanceTRX = $this->tron->getBalance(config('services.tron.wallet'));
            if ($balanceTRX < 10) { // Минимум 10 TRX для газа
                throw new \Exception('Недостаточно TRX для газа');
            }

            // Проверяем баланс USDT
            $contract = $this->tron->contract(env('USDT_CONTRACT'));
            $balance = $contract->balanceOf(config('services.tron.wallet'));
            $amount = (float)$commission['amount'];

            if ($balance < $amount) {
                throw new \Exception("Недостаточно USDT на счету: {$balance} требуется: {$amount}");
            }

            // Совершаем перевод
            $transaction = $contract->transfer(
                $commission['wallet'],
                $amount,
                config('services.tron.wallet')
            );

            // Проверяем статус транзакции
            if (!$transaction['result'] ?? false) {
                throw new \Exception('Транзакция не прошла: ' . ($transaction['txid'] ?? 'Нет TXID'));
            }

            // Отмечаем как оплаченное
            $this->apiClient->post("/api/commissions/{$commissionId}/mark_as_paid");

            return response()->json([
                'status' => 'success',
                'txid' => $transaction['txid']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validated['username'] === config('services.auth.username') &&
            $validated['password'] === config('services.auth.password')) {
            Session::put('authenticated', true);
            return redirect()->route('home');
        }

        return redirect()->route('login')->with('error', 'Неверные данные');
    }

    public function logout()
    {
        Session::forget('authenticated');
        return redirect()->route('login');
    }
    public function loginForm()
    {
        return view('auth.login');
    }
}
