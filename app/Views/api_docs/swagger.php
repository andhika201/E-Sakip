<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API e-SAKIP</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.32.4/swagger-ui.css">
    <style>
        body {
            margin: 0;
            background: #f8f9fa;
        }

        .swagger-ui .topbar {
            background-color: #198754;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.32.4/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.32.4/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: 'openapi.json',
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: 'StandaloneLayout',
                deepLinking: true,
                persistAuthorization: true
            });
        };
    </script>
</body>
</html>
