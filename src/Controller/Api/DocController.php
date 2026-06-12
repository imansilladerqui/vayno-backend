<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DocController
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $kernelEnvironment,
    ) {
    }

    #[Route('/api/doc', name: 'api_doc', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyInProduction();

        return new Response($this->swaggerHtml(), Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

    #[Route('/api/doc/openapi.yaml', name: 'api_doc_openapi', methods: ['GET'])]
    public function openapi(): Response
    {
        $this->denyInProduction();

        $path = $this->projectDir.'/config/openapi.yaml';
        if (!is_readable($path)) {
            throw new NotFoundHttpException('OpenAPI specification not found');
        }

        return new Response((string) file_get_contents($path), Response::HTTP_OK, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }

    private function denyInProduction(): void
    {
        if ($this->kernelEnvironment === 'prod') {
            throw new NotFoundHttpException();
        }
    }

    private function swaggerHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vayno API Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css">
  <style>body { margin: 0; } .topbar { display: none; }</style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
  <script>
    SwaggerUIBundle({
      url: '/api/doc/openapi.yaml',
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
      layout: 'BaseLayout',
    });
  </script>
</body>
</html>
HTML;
    }
}
