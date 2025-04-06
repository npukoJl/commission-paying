<!DOCTYPE html>
<html>
<head>
    <title>Список заявок</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <h1>Список заявок</h1>

    <div id="app">
        @foreach($commissions as $commission)
            <div class="commission-item" data-id="{{ $commission['id'] }}">
                <p>Имя: {{ $commission['username'] }}</p>
                <p>Кол-во: {{ $commission['amount'] }} USDT</p>
                <p>Кошелек: {{ $commission['wallet'] }}</p>

                <button
                    class="pay-button"
                    onclick="handlePayment({{ $commission['id'] }})"
                    {{ $commission['amount'] <= 0 ? 'disabled' : '' }}
                >
                    Pay
                </button>

                <div class="status"></div>
            </div>
            <hr>
        @endforeach
    </div>

    <script>
        function handlePayment(commissionId) {
    const button = document.querySelector(`button[onclick="handlePayment(${commissionId})"]`);
    button.disabled = true;

    axios.post(`/pay/${commissionId}`)
        .then(response => {
            if(response.data.status === 'success') {
                // Скрываем кнопку и показываем статус
                button.parentElement.querySelector('.status').innerHTML =
                    `✅ Успешно! TXID: ${response.data.txid}`;
                button.remove();
            }
        })
        .catch(error => {
            button.disabled = false;
            const errorMsg = error.response?.data?.message || 'Ошибка сервера';
            button.parentElement.querySelector('.status').innerHTML =
                `❌ Ошибка: ${errorMsg}`;
            console.error(error);
        });
}
    </script>
</body>
</html>
