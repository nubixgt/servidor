<?php

require_once 'config/database.php';

$nombreCliente = Trim($nombre." ".$apellido);
                    $mensajetxtW = "🌳🏡 Hola, *$nombreCliente* \\n\\nTal como conversamos, muy pronto estaremos ampliando la información completa sobre los lotes disponibles, incluyendo:\\n\\n📐 Medidas\\n💰 Precios\\n📍 Ubicación exacta\\n💳 Opciones de pago\\n\\nMientras tanto, te compartimos algunas fotografías del avance y el render del proyecto para que puedas visualizar mejor el desarrollo de Lotificación La Ceiba.\\n\\n✅ Quedamos atentos si deseas que te enviemos primero la ubicación o los precios actualizados.\\n\\n📲 Para cualquier información adicional, también puedes escribirnos por WhatsApp al 3256-0115 o por nuestras redes sociales.\\n\\nUn gusto saludarte. ¡Pronto estaremos en contacto! ✨🏡";

    // LIMPIAR y NORMALIZAR TELÉFONO GUATEMALA
$telefono = "50256965489";
$telefonoGT = trim($telefono);
$telefonoGT = preg_replace('/\s+/u', '', $telefonoGT); // borra espacios invisibles
$telefonoGT = preg_replace('/[^0-9]/u', '', $telefonoGT); // borra todo lo que no sea número
echo "ssiii";
if (!empty($telefonoGT) && strlen($telefonoGT) >= 8) {

    // Payload Guatemala
    $payloadGT = [
        "phone" => "+".$telefonoGT,
        "priority" => "urgent",
        "device" => "691c9cbbc9d11d53fdac2a69",
        "message" => $mensajetxtW,
        "media" => [
            "url" => "http://villaslaceibagt.com/assets/images/2025112141138.jpeg"
        ]
    ];
    
    enviarWassenger($payloadGT);
}