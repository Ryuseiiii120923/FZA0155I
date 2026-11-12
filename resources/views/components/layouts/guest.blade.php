<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HF Defect</title>
    <link rel="icon" href="{{ asset('images/fuji_logo.ico') }}" type="image/x-icon">
     @vite(['resources/css/app.css', 'resources/js/app.js'])
     @livewireScripts
    <script>
        let isRedirecting = false;

        Livewire.hook('request', ({
            fail
        }) => {
            fail(({
                status,
                preventDefault
            }) => {
                if (status === 419) {
                    preventDefault();

                   if (!isRedirecting) {
    isRedirecting = true;
    window.location.href = "{{ route('login') }}";
}
                }
            });
        });
    </script>
</head>
<body>
    {{ $slot }}
</body>
</html>