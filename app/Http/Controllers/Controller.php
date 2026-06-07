<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Forum Diskusi API Documentation",
    description: "API documentation for the Final Project Forum Diskusi",
    contact: new OA\Contact(email: "admin@example.com"),
    license: new OA\License(name: "Apache 2.0", url: "http://www.apache.org/licenses/LICENSE-2.0.html")
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "Demo API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
    //
}
