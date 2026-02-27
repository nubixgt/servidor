// app_uba/lib/main.dart

import 'package:flutter/material.dart';
import 'pantallas/pantalla_inicio.dart';
import 'pantallas/pantalla_principal.dart'; // ⬅️ importa la nueva pantalla
import 'utilidades/colores.dart';

void main() {
  runApp(const AppUBA());
}

class AppUBA extends StatelessWidget {
  const AppUBA({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'UBA Guatemala',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primaryColor: AppColores.azulPrimario,
        scaffoldBackgroundColor: AppColores.grisClaro,
        fontFamily: 'Roboto',
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: AppColores.azulPrimario),
      ),
      home: const PantallaPrincipal(), // ⬅️ ahora inicia en la bienvenida
      // (Opcional) Rutas con nombre para navegación futura
      routes: {
        '/inicio': (_) => const PantallaInicio(),
        '/bienvenida': (_) => const PantallaPrincipal(),
      },
    );
  }
}
