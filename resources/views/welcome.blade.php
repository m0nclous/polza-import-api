<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
    </head>
    <body class="antialiased">
        <button id="huy">Начать</button>

        <script>
            async function syncImages(cursor) {
                const response = await fetch('api/import/images/products/sync?' + new URLSearchParams({
                    cursor: cursor || 0,
                })).then((response) => response.json());

                if (response.cursor) await syncImages(response.cursor);
            }

            document.getElementById('huy').addEventListener('click', () => {
                syncImages();
            })
        </script>
    </body>
</html>
