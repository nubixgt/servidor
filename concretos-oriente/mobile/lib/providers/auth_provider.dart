import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthProvider with ChangeNotifier {
  String? _userRole;

  String? get userRole => _userRole;
  bool get isAuthenticated => _userRole != null;

  AuthProvider() {
    _loadUserRole();
  }

  Future<void> _loadUserRole() async {
    final prefs = await SharedPreferences.getInstance();
    _userRole = prefs.getString('userRole');
    notifyListeners();
  }

  Future<void> login(String role) async {
    _userRole = role;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('userRole', role);
    notifyListeners();
  }

  Future<void> logout() async {
    _userRole = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('userRole');
    notifyListeners();
  }
}
