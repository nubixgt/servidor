import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'theme/app_theme.dart';
import 'screens/onboarding_screen.dart';
import 'screens/main_shell.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const AppUBA());
}

class AppUBA extends StatelessWidget {
  const AppUBA({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'AppUBA',
      theme: AppTheme.light(),
      // Se muestra el onboarding cada vez que se abre la app por requerimiento
      home: const OnboardingScreen(),
      routes: {
        '/home': (context) => const MainShell(),
      },
    );
  }
}
