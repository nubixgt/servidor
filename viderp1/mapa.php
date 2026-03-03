<?php
/**
 * Mapa Interactivo con Leaflet.js - VIDER
 * MAGA Guatemala
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin(); // Proteger página - requiere autenticación

$currentPage = 'mapa';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>Mapa Interactivo - VIDER | MAGA</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* ========================================
           DISEÑO AZUL INSTITUCIONAL MAGA
           ======================================== */

        :root {
            --gradient-primary: linear-gradient(135deg, #1a3a5c 0%, #2d5a87 50%, #4a90d9 100%);
            --gradient-secondary: linear-gradient(135deg, #080c12 0%, #0d1520 50%, #141e2d 100%);
            --gradient-accent: linear-gradient(135deg, #4a90d9 0%, #6bb3ff 50%, #38bdf8 100%);
            --gradient-card: linear-gradient(145deg, rgba(74, 144, 217, 0.1) 0%, rgba(74, 144, 217, 0.05) 100%);
            --glass-bg-enhanced: rgba(20, 30, 45, 0.85);
            --glass-blur-enhanced: blur(20px);
            --glass-border-enhanced: rgba(74, 144, 217, 0.2);
        }

        .map-page {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 1.5rem;
            height: calc(100vh - 140px);
            padding: 1rem;
        }

        .map-container-full {
            background: var(--glass-bg-enhanced);
            backdrop-filter: var(--glass-blur-enhanced);
            -webkit-backdrop-filter: var(--glass-blur-enhanced);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 24px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border-enhanced);
        }

        .map-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .map-title h2 {
            margin: 0;
            font-size: 1.25rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .map-title .location-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.4rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(74, 144, 217, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 4px 15px rgba(74, 144, 217, 0.4);
            }

            50% {
                box-shadow: 0 4px 25px rgba(74, 144, 217, 0.6);
            }
        }

        .map-controls {
            display: flex;
            gap: 0.5rem;
        }

        .map-controls button {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--glass-border-enhanced);
            background: var(--glass-bg-enhanced);
            backdrop-filter: blur(10px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-secondary);
        }

        .map-controls button:hover {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 217, 0.4);
        }

        #map {
            flex: 1;
            border-radius: 16px;
            overflow: hidden;
            z-index: 1;
            min-height: 400px;
        }

        /* Leaflet custom styles */
        .leaflet-container {
            background: #1a1a2e;
            font-family: 'Outfit', sans-serif;
        }

        .leaflet-control-zoom {
            border: none !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
        }

        .leaflet-control-zoom a {
            background: var(--glass-bg-enhanced) !important;
            backdrop-filter: blur(10px) !important;
            color: white !important;
            border: 1px solid var(--glass-border-enhanced) !important;
            transition: all 0.3s ease !important;
        }

        .leaflet-control-zoom a:hover {
            background: var(--primary) !important;
        }

        .leaflet-popup-content-wrapper {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 12px;
            color: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .leaflet-popup-tip {
            background: rgba(26, 26, 46, 0.95);
        }

        .leaflet-popup-content {
            margin: 12px 16px;
        }

        .popup-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: var(--accent);
        }

        .popup-stats {
            display: grid;
            gap: 0.35rem;
            font-size: 0.85rem;
        }

        .popup-stat {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
        }

        .popup-stat-label {
            color: var(--text-secondary);
        }

        .popup-stat-value {
            font-weight: 600;
            color: white;
        }

        .popup-subtitle {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .popup-action {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.75rem;
            color: var(--accent);
            text-align: center;
            font-weight: 500;
        }

        /* Info Panel */
        .info-panel {
            background: var(--glass-bg-enhanced);
            backdrop-filter: var(--glass-blur-enhanced);
            -webkit-backdrop-filter: var(--glass-blur-enhanced);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 24px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            overflow-y: auto;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.6s ease-out 0.1s backwards;
        }

        .info-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border-enhanced);
        }

        .info-header h3 {
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-header h3 i {
            color: var(--accent);
        }

        .selected-area {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .selected-area-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .selected-area-name {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .stat-item {
            background: var(--gradient-card);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .stat-item.highlight {
            grid-column: span 2;
            background: var(--gradient-primary);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-item.highlight .stat-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .gender-chart-container {
            background: var(--gradient-card);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 16px;
            padding: 1rem;
        }

        .gender-chart-title {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .gender-chart-title i {
            color: var(--accent);
        }

        #genderChart {
            max-height: 160px;
        }

        .municipalities-list {
            background: var(--gradient-card);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 16px;
            padding: 1rem;
            flex: 1;
            overflow-y: auto;
            min-height: 150px;
        }

        .municipalities-title {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .municipalities-title i {
            color: var(--accent);
        }

        .municipality-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0.75rem;
            border-radius: 10px;
            margin-bottom: 0.35rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .municipality-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--glass-border-enhanced);
            transform: translateX(5px);
        }

        .municipality-item.active {
            background: rgba(74, 144, 217, 0.2);
            border-color: var(--primary);
        }

        .municipality-name {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .municipality-count {
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: 600;
            background: rgba(255, 193, 7, 0.15);
            padding: 0.2rem 0.5rem;
            border-radius: 8px;
        }

        /* Rank badges for top departments */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .municipality-item.gold {
            background: rgba(255, 215, 0, 0.1);
            border-color: rgba(255, 215, 0, 0.3);
        }

        .municipality-item.gold .rank-badge {
            background: linear-gradient(135deg, #ffd700 0%, #ffb300 100%);
            color: #1a1a2e;
        }

        .municipality-item.silver {
            background: rgba(192, 192, 192, 0.1);
            border-color: rgba(192, 192, 192, 0.3);
        }

        .municipality-item.silver .rank-badge {
            background: linear-gradient(135deg, #c0c0c0 0%, #a0a0a0 100%);
            color: #1a1a2e;
        }

        .municipality-item.bronze {
            background: rgba(205, 127, 50, 0.1);
            border-color: rgba(205, 127, 50, 0.3);
        }

        .municipality-item.bronze .rank-badge {
            background: linear-gradient(135deg, #cd7f32 0%, #b5651d 100%);
            color: white;
        }

        /* Legend */
        .map-legend {
            position: absolute;
            bottom: 1.5rem;
            left: 1.5rem;
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            z-index: 1000;
        }

        .legend-title {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .legend-scale {
            display: flex;
            gap: 0.25rem;
        }

        .legend-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .legend-color {
            width: 28px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-label {
            font-size: 0.65rem;
            color: var(--text-secondary);
        }

        /* Map Tooltip Styling */
        .map-tooltip {
            background: rgba(26, 26, 46, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-enhanced) !important;
            border-radius: 10px !important;
            color: white !important;
            padding: 8px 14px !important;
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .map-tooltip::before {
            border-top-color: rgba(26, 26, 46, 0.95) !important;
        }

        .leaflet-tooltip-left.map-tooltip::before {
            border-left-color: rgba(26, 26, 46, 0.95) !important;
        }

        .leaflet-tooltip-right.map-tooltip::before {
            border-right-color: rgba(26, 26, 46, 0.95) !important;
        }

        /* Back button */
        .back-btn {
            display: none;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--glass-bg-enhanced);
            border: 1px solid var(--glass-border-enhanced);
            border-radius: 10px;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .back-btn.visible {
            display: flex;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .map-page {
                grid-template-columns: 1fr 350px;
            }
        }

        @media (max-width: 1024px) {
            .page-header {
                padding-top: 3.5rem;
            }

            .map-page {
                padding-top: 0.5rem;
            }
        }

        @media (max-width: 992px) {
            .map-page {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                height: auto;
                gap: 1rem;
            }

            .map-container-full {
                min-height: 400px;
            }

            .info-panel {
                max-height: none;
            }

            .map-header {
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .map-title {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding-top: 4rem;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .header-subtitle {
                font-size: 0.85rem;
            }

            .map-page {
                padding: 0.75rem;
            }

            .map-container-full {
                padding: 1rem;
                border-radius: 16px;
                min-height: 350px;
            }

            .info-panel {
                padding: 1rem;
                border-radius: 16px;
            }

            /* Reorganize map header for mobile - stack vertically */
            .map-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                position: relative !important;
                margin-bottom: 0.75rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid rgba(74, 144, 217, 0.3);
            }

            /* Title row - first block */
            .map-title {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                gap: 0.5rem;
                width: 100%;
                margin-bottom: 0.75rem;
                padding: 0;
                border-bottom: none;
            }

            .map-title h2 {
                font-size: 1rem;
                width: 100%;
                text-align: center;
                margin: 0;
                order: 1;
            }

            .location-badge {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
                order: 2;
            }

            /* Controls row - second block, below title - FIXED positioning */
            .map-controls {
                display: flex !important;
                position: static !important;
                justify-content: center;
                align-items: center;
                flex-wrap: wrap;
                gap: 0.5rem;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .map-controls button {
                width: 36px;
                height: 36px;
                position: static !important;
            }

            /* Back button styling for mobile */
            .back-btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.75rem;
                position: static !important;
            }

            .back-btn.visible {
                display: flex;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .stat-item {
                padding: 0.75rem;
            }

            .stat-value {
                font-size: 1.25rem;
            }

            .stat-label {
                font-size: 0.65rem;
            }

            .map-legend {
                bottom: 0.75rem;
                left: 0.75rem;
                padding: 0.5rem 0.75rem;
                font-size: 0.65rem;
            }

            .legend-color {
                width: 16px;
                height: 8px;
            }

            #genderChart {
                max-height: 120px;
            }

            .municipalities-list {
                min-height: 120px;
                max-height: 200px;
            }
        }

        @media (max-width: 576px) {
            .map-page {
                padding: 0.5rem;
                gap: 0.75rem;
            }

            .map-container-full {
                min-height: 300px;
                padding: 0.75rem;
            }

            /* Map header completely stacked */
            .map-header {
                gap: 0.5rem;
            }

            .map-title h2 {
                font-size: 0.9rem;
            }

            .map-controls {
                width: 100%;
                justify-content: center;
                position: static !important;
                flex-wrap: wrap;
            }

            .map-controls button {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
                position: static !important;
            }

            .back-btn {
                width: auto;
                min-width: 70px;
                position: static !important;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.4rem;
            }

            .stat-item.highlight {
                grid-column: span 1;
            }

            .stat-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.6rem 0.75rem;
            }

            .stat-value {
                font-size: 1.1rem;
                order: 2;
            }

            .stat-label {
                order: 1;
                text-align: left;
            }

            /* Hide legend on very small screens or make it minimal */
            .map-legend {
                font-size: 0.6rem;
                padding: 0.4rem 0.5rem;
            }

            .legend-color {
                width: 12px;
                height: 6px;
            }

            .legend-item span {
                display: none;
            }

            /* Gender chart */
            .gender-chart-container {
                padding: 0.75rem;
            }

            .gender-chart-title {
                font-size: 0.8rem;
            }

            #genderChart {
                max-height: 100px;
            }

            /* Municipalities list */
            .municipalities-list {
                max-height: 150px;
            }

            .municipality-item {
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .info-panel {
                padding: 0.75rem;
            }

            .panel-header h3 {
                font-size: 0.9rem;
            }

            .selected-area-name {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 400px) {
            .map-container-full {
                min-height: 260px;
            }

            .map-title {
                flex-direction: column;
                gap: 0.3rem;
            }

            .map-controls button {
                width: 28px;
                height: 28px;
                font-size: 0.7rem;
                border-radius: 8px;
                position: static !important;
            }

            .back-btn {
                font-size: 0.7rem;
                padding: 0.35rem 0.5rem;
                position: static !important;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <h1><i class="fas fa-map-marked-alt"></i> Mapa Interactivo</h1>
                    <p class="header-subtitle">Distribución geográfica de beneficiarios</p>
                </div>
            </header>

            <div class="map-page">
                <!-- Map Container -->
                <div class="map-container-full">
                    <div class="map-header">
                        <div class="map-title">
                            <h2><i class="fas fa-globe-americas"></i> Guatemala</h2>
                            <span class="location-badge" id="currentLevel">22 Departamentos</span>
                        </div>
                        <div class="map-controls">
                            <button id="backBtn" class="back-btn" onclick="resetMap()">
                                <i class="fas fa-arrow-left"></i>
                                <span>Volver</span>
                            </button>
                            <button onclick="zoomIn()" title="Acercar">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button onclick="zoomOut()" title="Alejar">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button onclick="resetMap()" title="Restablecer">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <div id="map"></div>

                    <!-- Legend -->
                    <div class="map-legend">
                        <div class="legend-title">Beneficiarios</div>
                        <div class="legend-scale">
                            <div class="legend-item">
                                <div class="legend-color" style="background: #2d8b47;"></div>
                                <span class="legend-label">0-500</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #5cb85c;"></div>
                                <span class="legend-label">500-1k</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #8bc34a;"></div>
                                <span class="legend-label">1k-2k</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #ffc107;"></div>
                                <span class="legend-label">2k-5k</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #ff9800;"></div>
                                <span class="legend-label">5k+</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="info-panel">
                    <div class="info-header">
                        <h3><i class="fas fa-info-circle"></i> Información</h3>
                    </div>

                    <div class="selected-area">
                        <span class="selected-area-label">Área Seleccionada</span>
                        <span class="selected-area-name" id="selectedAreaName">Guatemala</span>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-item highlight">
                            <div class="stat-value" id="totalBeneficiarios">0</div>
                            <div class="stat-label">Total Beneficiarios</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="totalHombres">0</div>
                            <div class="stat-label">Hombres</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="totalMujeres">0</div>
                            <div class="stat-label">Mujeres</div>
                        </div>
                    </div>

                    <div class="gender-chart-container">
                        <div class="gender-chart-title">
                            <i class="fas fa-venus-mars"></i>
                            Distribución por Género
                        </div>
                        <canvas id="genderChart"></canvas>
                    </div>

                    <div class="municipalities-list" id="municipalitiesContainer">
                        <div class="municipalities-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <span id="municipalitiesTitle">Departamentos</span>
                        </div>
                        <div id="municipalitiesList">
                            <p
                                style="color: var(--text-secondary); font-size: 0.85rem; text-align: center; padding: 1rem;">
                                Seleccione un departamento para ver sus municipios
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/topojson-client@3"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Map initialization variables

        // Map variables
        let map;
        let deptosLayer;
        let munisLayer;
        let deptosData;
        let munisData;
        let currentDepartment = null;
        let genderChart;
        let mapData = {};

        // Helper function to normalize text for comparison
        function normalizeText(text) {
            if (!text) return '';
            return text.toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '') // Remove accents
                .trim();
        }

        // Helper function to find data in mapData with flexible matching
        function findMapData(name) {
            if (!name) return { beneficiarios: 0, hombres: 0, mujeres: 0, programado: 0, ejecutado: 0 };

            // Direct match first
            if (mapData[name]) {
                return mapData[name];
            }

            // Normalized search
            const normalizedName = normalizeText(name);
            for (let key in mapData) {
                if (normalizeText(key) === normalizedName) {
                    return mapData[key];
                }
            }

            // Partial match as fallback
            for (let key in mapData) {
                if (normalizeText(key).includes(normalizedName) || normalizedName.includes(normalizeText(key))) {
                    return mapData[key];
                }
            }

            return { beneficiarios: 0, hombres: 0, mujeres: 0, programado: 0, ejecutado: 0 };
        }

        // Guatemala bounds
        const guatemalaBounds = [[13.7, -92.3], [18.0, -88.2]];
        const guatemalaCenter = [15.5, -90.25];

        // Color scale for beneficiaries
        function getColor(beneficiarios) {
            return beneficiarios > 5000 ? '#ff9800' :
                beneficiarios > 2000 ? '#ffc107' :
                    beneficiarios > 1000 ? '#8bc34a' :
                        beneficiarios > 500 ? '#5cb85c' :
                            '#2d8b47';
        }

        // Style for department polygons
        function deptStyle(feature) {
            const deptName = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre || 'Unknown';
            const data = findMapData(deptName);

            return {
                fillColor: getColor(data.beneficiarios),
                weight: 2,
                opacity: 1,
                color: 'rgba(255, 255, 255, 0.4)',
                fillOpacity: 0.7
            };
        }

        // Style for municipality polygons
        function muniStyle(feature) {
            const muniName = feature.properties.Municipio || feature.properties.NOMBRE || feature.properties.nombre || 'Unknown';
            const data = findMapData(muniName);

            return {
                fillColor: getColor(data.beneficiarios),
                weight: 1.5,
                opacity: 1,
                color: 'rgba(255, 255, 255, 0.5)',
                fillOpacity: 0.75
            };
        }

        // Highlight style on hover
        function highlightFeature(e) {
            const layer = e.target;
            layer.setStyle({
                weight: 3,
                color: '#ffc107',
                fillOpacity: 0.9
            });
            layer.bringToFront();
        }

        // Reset highlight
        function resetHighlight(e) {
            if (deptosLayer) deptosLayer.resetStyle(e.target);
            if (munisLayer) munisLayer.resetStyle(e.target);
        }

        // Department click handler
        function onDeptClick(e) {
            const feature = e.target.feature;
            const deptName = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre;
            const deptCode = feature.properties.id;

            currentDepartment = {
                name: deptName,
                code: deptCode,
                bounds: e.target.getBounds()
            };

            // Animate zoom to department
            map.flyToBounds(currentDepartment.bounds, {
                padding: [50, 50],
                duration: 0.8
            });

            // Show municipalities for this department
            showMunicipalities(deptCode, deptName);

            // Update UI
            document.getElementById('selectedAreaName').textContent = deptName;
            document.getElementById('currentLevel').textContent = 'Departamento';
            document.getElementById('backBtn').classList.add('visible');

            // Load department data
            loadDepartmentData(deptName);
        }

        // Municipality click handler
        function onMuniClick(e) {
            const feature = e.target.feature;
            const muniName = feature.properties.Municipio || feature.properties.NOMBRE || feature.properties.nombre;

            // Zoom to municipality
            map.flyToBounds(e.target.getBounds(), {
                padding: [80, 80],
                duration: 0.5
            });

            // Update UI
            document.getElementById('selectedAreaName').textContent = muniName;
            document.getElementById('currentLevel').textContent = 'Municipio';

            // Load municipality data
            loadMunicipalityData(muniName);
        }

        // Add interactivity to each feature
        function onEachDept(feature, layer) {
            const name = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre || 'Desconocido';

            // Add tooltip to show name on hover
            layer.bindTooltip(name, {
                permanent: false,
                direction: 'top',
                className: 'map-tooltip',
                offset: [0, -10]
            });

            // Create dynamic popup that updates with current data
            layer.bindPopup(() => {
                const data = findMapData(name);
                return `
                    <div class="popup-title">${name}</div>
                    <div class="popup-subtitle">Departamento</div>
                    <div class="popup-stats">
                        <div class="popup-stat">
                            <span class="popup-stat-label">Beneficiarios:</span>
                            <span class="popup-stat-value">${data.beneficiarios.toLocaleString()}</span>
                        </div>
                        <div class="popup-stat">
                            <span class="popup-stat-label">Hombres:</span>
                            <span class="popup-stat-value">${data.hombres.toLocaleString()}</span>
                        </div>
                        <div class="popup-stat">
                            <span class="popup-stat-label">Mujeres:</span>
                            <span class="popup-stat-value">${data.mujeres.toLocaleString()}</span>
                        </div>
                    </div>
                    <div class="popup-action">Clic para ver municipios</div>
                `;
            });

            layer.on({
                mouseover: highlightFeature,
                mouseout: resetHighlight,
                click: onDeptClick
            });
        }

        function onEachMuni(feature, layer) {
            const name = feature.properties.Municipio || feature.properties.NOMBRE || feature.properties.nombre || 'Desconocido';

            // Add tooltip to show name on hover
            layer.bindTooltip(name, {
                permanent: false,
                direction: 'top',
                className: 'map-tooltip',
                offset: [0, -10]
            });

            // Create dynamic popup that updates with current data
            layer.bindPopup(() => {
                const data = findMapData(name);
                const porcentaje = data.programado > 0 ? ((data.ejecutado / data.programado) * 100).toFixed(1) : 0;

                let ejecutadoHtml = '';
                if (data.programado > 0) {
                    ejecutadoHtml = `
                        <div class="popup-stat">
                            <span class="popup-stat-label">Ejecución:</span>
                            <span class="popup-stat-value" style="color: ${porcentaje >= 80 ? '#22c55e' : porcentaje >= 50 ? '#f59e0b' : '#ef4444'};">
                                ${porcentaje}%
                            </span>
                        </div>
                    `;
                }

                return `
                    <div class="popup-title">${name}</div>
                    <div class="popup-subtitle">Municipio</div>
                    <div class="popup-stats">
                        <div class="popup-stat">
                            <span class="popup-stat-label">Beneficiarios:</span>
                            <span class="popup-stat-value">${data.beneficiarios.toLocaleString()}</span>
                        </div>
                        <div class="popup-stat">
                            <span class="popup-stat-label">Hombres:</span>
                            <span class="popup-stat-value">${data.hombres.toLocaleString()}</span>
                        </div>
                        <div class="popup-stat">
                            <span class="popup-stat-label">Mujeres:</span>
                            <span class="popup-stat-value">${data.mujeres.toLocaleString()}</span>
                        </div>
                        ${ejecutadoHtml}
                    </div>
                `;
            });

            layer.on({
                mouseover: highlightFeature,
                mouseout: function (e) {
                    if (munisLayer) munisLayer.resetStyle(e.target);
                },
                click: function (e) {
                    const muniName = feature.properties.Municipio || feature.properties.NOMBRE || feature.properties.nombre;

                    // Zoom to municipality
                    map.flyToBounds(e.target.getBounds(), {
                        padding: [80, 80],
                        duration: 0.5
                    });

                    // Update UI
                    document.getElementById('selectedAreaName').textContent = muniName;
                    document.getElementById('currentLevel').textContent = 'Municipio';

                    // Load municipality data
                    loadMunicipalityData(muniName);

                    // Open popup
                    e.target.openPopup();
                }
            });
        }

        // Show municipalities layer
        function showMunicipalities(deptCode, deptName) {
            if (!munisData) {
                console.log('No municipalities data available');
                return;
            }

            console.log(`Showing municipalities for: ${deptName} (code: ${deptCode})`);

            // Hide departments layer completely to allow municipality clicks
            if (deptosLayer) {
                map.removeLayer(deptosLayer);
            }

            // Remove existing municipalities layer
            if (munisLayer) {
                map.removeLayer(munisLayer);
            }

            // Filter municipalities by department id
            const filteredMunis = {
                type: 'FeatureCollection',
                features: munisData.features.filter(f => {
                    // Use id_depto property which matches the department id
                    return f.properties.id_depto == deptCode;
                })
            };

            console.log(`Found ${filteredMunis.features.length} municipalities`);

            if (filteredMunis.features.length === 0) {
                console.log('No municipalities found for this department');
                return;
            }

            // Add municipalities layer
            munisLayer = L.geoJSON(filteredMunis, {
                style: muniStyle,
                onEachFeature: onEachMuni
            }).addTo(map);

            // Bring municipalities layer to front
            munisLayer.bringToFront();

            // Update municipalities list
            updateMunicipalitiesList(filteredMunis.features);
        }

        // Update municipalities list in sidebar
        function updateMunicipalitiesList(features) {
            const container = document.getElementById('municipalitiesList');
            document.getElementById('municipalitiesTitle').textContent = 'Municipios';

            if (features.length === 0) {
                container.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.85rem; text-align: center; padding: 1rem;">No hay municipios disponibles</p>';
                return;
            }

            let html = '';
            features.forEach(f => {
                const name = f.properties.Municipio || f.properties.NOMBRE || f.properties.nombre || 'Desconocido';
                const data = findMapData(name);
                html += `
                    <div class="municipality-item" onclick="zoomToMunicipality('${name}')">
                        <span class="municipality-name">${name}</span>
                        <span class="municipality-count">${data.beneficiarios.toLocaleString()}</span>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Zoom to specific municipality
        function zoomToMunicipality(name) {
            if (!munisLayer) return;

            munisLayer.eachLayer(layer => {
                const layerName = layer.feature.properties.Municipio || layer.feature.properties.NOMBRE || layer.feature.properties.nombre;
                if (layerName === name) {
                    map.flyToBounds(layer.getBounds(), {
                        padding: [80, 80],
                        duration: 0.5
                    });
                    layer.openPopup();
                    document.getElementById('selectedAreaName').textContent = name;
                    document.getElementById('currentLevel').textContent = 'Municipio';

                    // Load municipality data
                    loadMunicipalityData(name);

                    // Highlight active item in list
                    document.querySelectorAll('.municipality-item').forEach(item => {
                        item.classList.remove('active');
                        if (item.textContent.includes(name)) {
                            item.classList.add('active');
                        }
                    });
                }
            });
        }

        // Zoom to specific department
        function zoomToDepartment(name) {
            if (!deptosLayer) return;

            deptosLayer.eachLayer(layer => {
                const layerName = layer.feature.properties.NOMBRE || layer.feature.properties.nombre;
                if (layerName === name) {
                    const deptCode = layer.feature.properties.CODIGO || layer.feature.properties.codigo;

                    // Set current department
                    currentDepartment = {
                        name: layerName,
                        code: deptCode,
                        bounds: layer.getBounds()
                    };

                    // Animate zoom to department
                    map.flyToBounds(layer.getBounds(), {
                        padding: [50, 50],
                        duration: 0.8
                    });

                    // Open popup
                    layer.openPopup();

                    // Show municipalities for this department
                    showMunicipalities(deptCode, layerName);

                    // Update UI
                    document.getElementById('selectedAreaName').textContent = layerName;
                    document.getElementById('currentLevel').textContent = 'Departamento';
                    document.getElementById('backBtn').classList.add('visible');

                    // Load department data
                    loadDepartmentData(layerName);

                    // Highlight active item in list
                    document.querySelectorAll('.municipality-item').forEach(item => {
                        item.classList.remove('active');
                    });
                }
            });
        }

        // Update departments list in sidebar
        function updateDepartmentsList(deptData) {
            const container = document.getElementById('municipalitiesList');
            document.getElementById('municipalitiesTitle').textContent = 'Departamentos';

            if (!deptData || deptData.length === 0) {
                container.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.85rem; text-align: center; padding: 1rem;">No hay datos disponibles</p>';
                return;
            }

            // Sort by beneficiaries descending
            const sorted = [...deptData].sort((a, b) => (parseInt(b.total) || 0) - (parseInt(a.total) || 0));

            let html = '';
            sorted.forEach((dept, index) => {
                const name = dept.departamento;
                const total = parseInt(dept.total) || 0;
                const rankClass = index === 0 ? 'gold' : index === 1 ? 'silver' : index === 2 ? 'bronze' : '';
                html += `
                    <div class="municipality-item ${rankClass}" onclick="zoomToDepartment('${name}')">
                        <span class="municipality-name">
                            ${index < 3 ? `<span class="rank-badge">${index + 1}</span>` : ''}
                            ${name}
                        </span>
                        <span class="municipality-count">${total.toLocaleString()}</span>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Reset map to initial view
        function resetMap() {
            currentDepartment = null;

            // Reset view
            map.flyToBounds(guatemalaBounds, {
                duration: 0.8
            });

            // Remove municipalities layer
            if (munisLayer) {
                map.removeLayer(munisLayer);
                munisLayer = null;
            }

            // Re-add departments layer if it was removed
            if (deptosLayer && !map.hasLayer(deptosLayer)) {
                deptosLayer.addTo(map);
            }

            // Restore departments layer styling
            if (deptosLayer) {
                deptosLayer.setStyle(deptStyle);
            }

            // Reset UI
            document.getElementById('selectedAreaName').textContent = 'Guatemala';
            document.getElementById('currentLevel').textContent = '22 Departamentos';
            document.getElementById('backBtn').classList.remove('visible');
            document.getElementById('municipalitiesTitle').textContent = 'Departamentos';

            // Load national data and update department list
            loadNationalData();
        }

        // Zoom controls
        function zoomIn() {
            map.zoomIn();
        }

        function zoomOut() {
            map.zoomOut();
        }

        // Load data for national level
        function loadNationalData() {
            fetch('api/get_map_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store data for map styling
                        data.data.forEach(item => {
                            mapData[item.departamento] = {
                                beneficiarios: parseInt(item.total) || 0,
                                hombres: parseInt(item.hombres) || 0,
                                mujeres: parseInt(item.mujeres) || 0
                            };
                        });

                        // Refresh map styles
                        if (deptosLayer) {
                            deptosLayer.setStyle(deptStyle);
                        }

                        // Calculate totals
                        let totalBenef = 0, totalH = 0, totalM = 0;
                        data.data.forEach(item => {
                            totalBenef += parseInt(item.total) || 0;
                            totalH += parseInt(item.hombres) || 0;
                            totalM += parseInt(item.mujeres) || 0;
                        });

                        updateStatsDisplay(totalBenef, totalH, totalM);

                        // Update departments list in the sidebar
                        updateDepartmentsList(data.data);
                    }
                })
                .catch(err => {
                    console.log('Using sample data');
                    // Sample data for demonstration
                    const sampleDepts = [
                        { departamento: 'Petén', total: 3500, hombres: 1800, mujeres: 1700 },
                        { departamento: 'Alta Verapaz', total: 4200, hombres: 2100, mujeres: 2100 },
                        { departamento: 'Quiché', total: 2800, hombres: 1400, mujeres: 1400 },
                        { departamento: 'Huehuetenango', total: 3100, hombres: 1550, mujeres: 1550 },
                        { departamento: 'San Marcos', total: 2500, hombres: 1250, mujeres: 1250 },
                        { departamento: 'Quetzaltenango', total: 2200, hombres: 1100, mujeres: 1100 },
                        { departamento: 'Sololá', total: 1800, hombres: 900, mujeres: 900 },
                        { departamento: 'Chimaltenango', total: 1500, hombres: 750, mujeres: 750 }
                    ];

                    sampleDepts.forEach(d => {
                        mapData[d.departamento] = {
                            beneficiarios: d.total,
                            hombres: d.hombres,
                            mujeres: d.mujeres
                        };
                    });

                    if (deptosLayer) deptosLayer.setStyle(deptStyle);

                    const total = sampleDepts.reduce((sum, d) => sum + d.total, 0);
                    const totalH = sampleDepts.reduce((sum, d) => sum + d.hombres, 0);
                    const totalM = sampleDepts.reduce((sum, d) => sum + d.mujeres, 0);

                    updateStatsDisplay(total, totalH, totalM);

                    // Update departments list with sample data
                    updateDepartmentsList(sampleDepts);
                });
        }

        // Load department-specific data
        function loadDepartmentData(deptName) {
            // First, get department totals from mapData using flexible matching
            const deptData = findMapData(deptName);
            updateStatsDisplay(deptData.beneficiarios, deptData.hombres, deptData.mujeres);

            // Then load municipalities data for this department
            fetch(`api/get_municipios.php?departamento=${encodeURIComponent(deptName)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        // Store municipalities data in mapData
                        data.data.forEach(muni => {
                            mapData[muni.municipio] = {
                                beneficiarios: parseInt(muni.total_beneficiarios) || 0,
                                hombres: parseInt(muni.total_hombres) || 0,
                                mujeres: parseInt(muni.total_mujeres) || 0,
                                programado: parseFloat(muni.total_programado) || 0,
                                ejecutado: parseFloat(muni.total_ejecutado) || 0
                            };
                        });

                        // Refresh municipalities layer styles with new data
                        if (munisLayer) {
                            munisLayer.setStyle(muniStyle);
                        }

                        // Update municipalities list with fresh data
                        if (munisLayer) {
                            const features = [];
                            munisLayer.eachLayer(layer => {
                                features.push(layer.feature);
                            });
                            updateMunicipalitiesList(features);
                        }

                        console.log(`Loaded ${data.data.length} municipalities for ${deptName}`);
                    }
                })
                .catch(err => {
                    console.log('Error loading municipalities data:', err);
                });
        }

        // Load municipality-specific data
        function loadMunicipalityData(muniName) {
            const data = findMapData(muniName);
            updateStatsDisplay(data.beneficiarios, data.hombres, data.mujeres);
        }

        // Update stats display with animation
        function updateStatsDisplay(total, hombres, mujeres) {
            animateValue('totalBeneficiarios', total);
            animateValue('totalHombres', hombres);
            animateValue('totalMujeres', mujeres);

            updateGenderChart(hombres, mujeres);
        }

        // Animate number counting
        function animateValue(elementId, endValue) {
            const element = document.getElementById(elementId);
            const startValue = parseInt(element.textContent.replace(/,/g, '')) || 0;
            const duration = 800;
            const startTime = performance.now();

            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                const currentValue = Math.round(startValue + (endValue - startValue) * easeProgress);
                element.textContent = currentValue.toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }

            requestAnimationFrame(update);
        }

        // Update gender chart
        function updateGenderChart(hombres, mujeres) {
            const ctx = document.getElementById('genderChart').getContext('2d');

            if (genderChart) {
                genderChart.data.datasets[0].data = [hombres, mujeres];
                genderChart.update('none');
            } else {
                genderChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Hombres', 'Mujeres'],
                        datasets: [{
                            data: [hombres, mujeres],
                            backgroundColor: ['#3b82f6', '#ec4899'],
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: 'rgba(255,255,255,0.8)',
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: { size: 12 }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Initialize map
        async function initMap() {
            // Create map
            map = L.map('map', {
                center: guatemalaCenter,
                zoom: 7,
                minZoom: 6,
                maxZoom: 14,
                zoomControl: false,
                attributionControl: false
            });

            // Add zoom control to top-right
            L.control.zoom({ position: 'topright' }).addTo(map);

            // Add dark tile layer
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 19
            }).addTo(map);

            // Load TopoJSON data
            try {
                const [deptosResponse, munisResponse] = await Promise.all([
                    fetch('js/deptos.json'),
                    fetch('js/munis.json')
                ]);

                const deptosTopoJSON = await deptosResponse.json();
                const munisTopoJSON = await munisResponse.json();

                // Convert TopoJSON to GeoJSON
                const deptosKey = Object.keys(deptosTopoJSON.objects)[0];
                const munisKey = Object.keys(munisTopoJSON.objects)[0];

                deptosData = topojson.feature(deptosTopoJSON, deptosTopoJSON.objects[deptosKey]);
                munisData = topojson.feature(munisTopoJSON, munisTopoJSON.objects[munisKey]);

                // Add departments layer
                deptosLayer = L.geoJSON(deptosData, {
                    style: deptStyle,
                    onEachFeature: onEachDept
                }).addTo(map);

                // Fit to Guatemala bounds
                map.fitBounds(guatemalaBounds);

                // Load national data
                loadNationalData();

            } catch (error) {
                console.error('Error loading map data:', error);
                document.getElementById('map').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary);">
                        <div style="text-align: center;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: var(--accent);"></i>
                            <p>Error cargando el mapa. Por favor recargue la página.</p>
                        </div>
                    </div>
                `;
            }
        }

        // Mobile Menu Functions
        function initMobileMenu() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebar-close');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            if (!menuToggle || !sidebar) return;

            function openSidebar() {
                sidebar.classList.add('open');
                sidebarOverlay.classList.add('show');
                menuToggle.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
                menuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }

            menuToggle.addEventListener('click', openSidebar);
            sidebarClose?.addEventListener('click', closeSidebar);
            sidebarOverlay?.addEventListener('click', closeSidebar);

            sidebar.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) closeSidebar();
                });
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024) closeSidebar();
            });
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function () {
            initMobileMenu();
            initMap();

            // Handle window resize to recenter map
            let resizeTimeout;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function () {
                    if (map) {
                        map.invalidateSize();
                        // Recenter map if no department is selected
                        if (!currentDepartment) {
                            map.fitBounds(guatemalaBounds);
                        }
                    }
                }, 250);
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>