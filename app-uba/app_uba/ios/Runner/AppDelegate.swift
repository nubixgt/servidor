import UIKit
import Flutter
import GoogleMaps  // ← NUEVO

@main
@objc class AppDelegate: FlutterAppDelegate {
  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    // ← NUEVO: Registra tu API Key de Google Maps
    GMSServices.provideAPIKey("AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g")
    
    GeneratedPluginRegistrant.register(with: self)
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }
}