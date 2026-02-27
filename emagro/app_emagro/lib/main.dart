import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'services/auth_service.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('es', null);
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Emagro',
      debugShowCheckedModeBanner: false,
      locale: const Locale('es', 'ES'),
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('es', 'ES'),
      ],
      theme: ThemeData(
        primarySwatch: Colors.blue,
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: const SplashScreen(),
    );
  }
}

class SplashScreen extends StatefulWidget {
  const SplashScreen({Key? key}) : super(key: key);

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _verificarSesion();
  }

  Future<void> _verificarSesion() async {
    // Esperar un momento para mostrar el splash
    await Future.delayed(const Duration(seconds: 2));

    if (!mounted) return;

    // Verificar si hay sesión activa
    final tieneSesion = await AuthService().verificarSesion();

    if (!mounted) return;

    // Navegar a la pantalla correspondiente
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => tieneSesion ? const HomeScreen() : const LoginScreen(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        color: Colors.white,
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Logo
              Image.asset(
                'assets/images/logo_emagro.png',
                width: 280,
                height: 180,
                fit: BoxFit.contain,
              ),
              const SizedBox(height: 20),

              // Subtítulo
              const Text(
                'Sistema de Gestión Agrícola',
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.black54,
                  letterSpacing: 1,
                ),
              ),
              const SizedBox(height: 50),

              // Loading indicator
              const CircularProgressIndicator(
                color: Colors.blue,
                strokeWidth: 3,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
