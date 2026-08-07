import 'package:flutter_test/flutter_test.dart';

import 'package:app_conadea/main.dart';

void main() {
  testWidgets('La app abre en la pantalla de Login', (WidgetTester tester) async {
    await tester.pumpWidget(const AgroIAApp());

    expect(find.text('Aula Virtual AgroIA'), findsOneWidget);
    expect(find.text('Iniciar Sesión'), findsOneWidget);
  });
}
