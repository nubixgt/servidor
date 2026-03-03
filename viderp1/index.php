<?php
/**
 * VIDER 2025 - Dashboard Principal
 * Basado en Looker Studio de MAGA Guatemala
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin(); // Proteger página - requiere autenticación

$currentPage = 'index';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>VIDER  | MAGA Guatemala</title>
    <style>
        .dashboard-title {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d5a87 50%, #4a90d9 100%);
            color: white;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(74, 144, 217, 0.3);
        }

        .dashboard-title::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmerTitle 3s ease-in-out infinite;
        }

        @keyframes shimmerTitle {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        .dashboard-title h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .dashboard-title p {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: rgba(20, 30, 45, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a3a5c, #4a90d9);
            transition: height 0.3s ease;
        }

        .metric-card.green::before {
            background: linear-gradient(90deg, #22c55e, #4ade80);
        }

        .metric-card.blue::before {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .metric-card.pink::before {
            background: linear-gradient(90deg, #ec4899, #f472b6);
        }

        .metric-card.orange::before {
            background: linear-gradient(90deg, #f97316, #fb923c);
        }

        .metric-card.purple::before {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }

        .metric-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(74, 144, 217, 0.25), 0 0 60px rgba(74, 144, 217, 0.1);
            border-color: rgba(74, 144, 217, 0.5);
        }

        .metric-card:hover::before {
            height: 6px;
            animation: rotateGradient 2s ease infinite;
            background-size: 200% 200%;
        }

        /* Shimmer overlay on card hover */
        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .metric-card:hover::after {
            left: 100%;
        }

        .metric-value {
            font-family: var(--font-display);
            font-size: clamp(1.2rem, 3vw, 2rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            word-break: break-all;
        }

        .metric-card.green .metric-value {
            color: #22c55e;
        }

        .metric-card.blue .metric-value {
            color: #3b82f6;
        }

        .metric-card.pink .metric-value {
            color: #ec4899;
        }

        .metric-card.orange .metric-value {
            color: #f97316;
        }

        .metric-card.purple .metric-value {
            color: #8b5cf6;
        }

        .metric-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .metric-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .execution-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .execution-card {
            background: rgba(20, 30, 45, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .execution-card:hover {
            border-color: rgba(74, 144, 217, 0.4);
            box-shadow: 0 15px 35px rgba(74, 144, 217, 0.15);
        }

        .execution-card h3 {
            font-family: var(--font-display);
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .execution-card h3 i {
            color: #4a90d9;
        }

        .execution-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .exec-stat {
            text-align: center;
            padding: 1rem;
            background: rgba(74, 144, 217, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(74, 144, 217, 0.15);
            transition: all 0.3s ease;
        }

        .exec-stat:hover {
            background: rgba(74, 144, 217, 0.15);
            transform: translateY(-2px);
        }

        .exec-stat-value {
            font-family: var(--font-display);
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            font-weight: 700;
            color: #4a90d9;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .exec-stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .progress-bar-large {
            height: 24px;
            background: rgba(20, 30, 45, 0.6);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(74, 144, 217, 0.15);
        }

        .progress-fill {
            height: 100%;
            border-radius: 12px;
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: progressShimmer 2s infinite;
        }

        @keyframes progressShimmer {
            100% {
                left: 100%;
            }
        }

        .progress-fill.physical {
            background: linear-gradient(90deg, #22c55e, #4ade80);
        }

        .progress-fill.financial {
            background: linear-gradient(90deg, #4a90d9, #6bb3ff);
        }

        /* Physical Table Styles */
        .physical-card {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(74, 144, 217, 0.1)), var(--glass-bg);
        }

        .physical-table {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .physical-header {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .physical-col.header {
            background: linear-gradient(135deg, #1a5f2a, #2d7a3e);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .physical-col.header:nth-child(2) {
            background: linear-gradient(135deg, #b8860b, #daa520);
        }

        .physical-col.header:nth-child(3) {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .physical-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .physical-row .physical-col {
            background: rgba(34, 197, 94, 0.25);
            border: 1px solid rgba(34, 197, 94, 0.5);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .physical-row .physical-col:nth-child(2) {
            background: rgba(218, 165, 32, 0.25);
            border-color: rgba(218, 165, 32, 0.5);
        }

        .physical-row .physical-col:nth-child(3) {
            background: rgba(37, 99, 235, 0.25);
            border-color: rgba(37, 99, 235, 0.5);
        }

        .row-label {
            font-size: 0.8rem;
            color: #ffffff;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
        }

        .row-value {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }

        .physical-row .physical-col:first-child .row-value {
            color: #4ade80;
            text-shadow: 0 0 8px rgba(74, 222, 128, 0.5), 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .physical-row .physical-col:nth-child(2) .row-value {
            color: #fcd34d;
            text-shadow: 0 0 8px rgba(252, 211, 77, 0.5), 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .physical-row .physical-col:nth-child(3) .row-value {
            color: #60a5fa;
            text-shadow: 0 0 8px rgba(96, 165, 250, 0.5), 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            overflow: hidden;
        }

        .chart-card h3 {
            font-family: var(--font-display);
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dep-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dep-table th {
            text-align: left;
            padding: 0.75rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--glass-border);
            font-weight: 600;
        }

        .dep-table td {
            padding: 0.75rem;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .dep-table tr:hover {
            background: rgba(74, 144, 217, 0.05);
        }

        .dep-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .dep-badge.direpro {
            background: rgba(139, 92, 246, 0.15);
            color: #8b5cf6;
        }

        .dep-badge.ddp {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .dep-badge.difoproco {
            background: rgba(236, 72, 153, 0.15);
            color: #ec4899;
        }

        .dep-badge.dda {
            background: rgba(74, 144, 217, 0.15);
            color: var(--primary);
        }

        .dep-badge.diprodu {
            background: rgba(249, 115, 22, 0.15);
            color: #f97316;
        }

        .map-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .dept-ranking {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .dept-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .dept-item:hover {
            background: rgba(74, 144, 217, 0.1);
        }

        .dept-rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .dept-item:nth-child(1) .dept-rank {
            background: #fbbf24;
        }

        .dept-item:nth-child(2) .dept-rank {
            background: #94a3b8;
        }

        .dept-item:nth-child(3) .dept-rank {
            background: #cd7f32;
        }

        .dept-info {
            flex: 1;
        }

        .dept-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .dept-stats {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .dept-value {
            font-family: var(--font-display);
            font-weight: 700;
            color: var(--primary);
            font-size: 1rem;
        }

        .filters-bar {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 100;
            overflow: visible;
            max-width: 100%;
            box-sizing: border-box;
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--glass-border);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .filters-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-title i {
            color: var(--primary);
        }

        .filters-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem 1.5rem;
            max-width: 100%;
        }

        .filter-dropdown {
            position: relative;
            min-width: 0;
            max-width: 100%;
            width: 100%;
        }

        .filter-dropdown-header {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .filter-dropdown-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .filter-dropdown-label i {
            color: var(--primary);
            font-size: 0.8rem;
        }

        .filter-dropdown-trigger {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--surface-primary);
            color: var(--text-primary);
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            height: 48px;
            min-height: 48px;
            max-height: 48px;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .filter-dropdown-trigger:hover {
            border-color: var(--primary);
            background: rgba(74, 144, 217, 0.05);
        }

        .filter-dropdown-trigger.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(74, 144, 217, 0.2);
        }

        .filter-trigger-text {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-secondary);
            padding-right: 0.5rem;
        }

        .filter-trigger-text.has-selection {
            color: var(--text-primary);
            font-weight: 500;
        }

        .filter-trigger-count {
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .filter-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 9999;
            background: var(--surface-primary);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            margin-top: 4px;
            display: none;
            max-height: 280px;
            overflow: hidden;
            flex-direction: column;
        }

        .filter-dropdown-menu.show {
            display: flex;
        }

        .filter-search-container {
            padding: 0.5rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .filter-search-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            padding-left: 2rem;
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            background: var(--surface-secondary);
            color: var(--text-primary);
            font-size: 0.8rem;
        }

        .filter-search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-search-wrapper {
            position: relative;
        }

        .filter-search-wrapper i {
            position: absolute;
            left: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        .filter-options-list {
            overflow-y: auto;
            max-height: 200px;
            padding: 0.25rem;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.15s ease;
        }

        .filter-option:hover {
            background: rgba(74, 144, 217, 0.1);
        }

        .filter-option input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .filter-option-label {
            flex: 1;
            font-size: 0.8rem;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .filter-option.selected {
            background: rgba(74, 144, 217, 0.15);
        }

        .filter-option.select-all-option {
            background: rgba(74, 144, 217, 0.05);
            border-bottom: 1px solid var(--glass-border);
            margin-bottom: 0.25rem;
            padding-bottom: 0.5rem;
        }

        .filter-option.select-all-option:hover {
            background: rgba(74, 144, 217, 0.15);
        }

        .filter-option.select-all-option.selected {
            background: rgba(34, 197, 94, 0.15);
        }

        .filter-no-results {
            padding: 1rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .filter-loading {
            padding: 1rem;
            text-align: center;
            color: var(--primary);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .filter-loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive filters */
        @media (max-width: 1024px) {
            .filters-header {
                flex-direction: column;
                gap: 0.75rem;
                align-items: stretch;
            }

            .filters-actions {
                justify-content: center;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .filters-bar {
                padding: 0.75rem 1rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filters-actions {
                flex-direction: row;
                gap: 0.5rem;
            }

            .filters-actions .btn {
                flex: 1;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .filters-bar {
                padding: 0.5rem 0.75rem;
                margin-bottom: 1rem;
            }

            .filters-title {
                font-size: 0.85rem;
            }

            .filters-actions .btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }

            .filter-dropdown-trigger {
                padding: 0.5rem 0.6rem;
                font-size: 0.8rem;
                min-height: 36px;
            }

            .filter-dropdown-label {
                font-size: 0.75rem;
            }
        }

        .dept-path {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dept-path:hover {
            fill: #fbbf24 !important;
            transform: scale(1.02);
        }

        .map-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 100;
        }

        /* Table wrapper for horizontal scroll */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -0.5rem;
            padding: 0 0.5rem;
        }

        /* =====================================================
           RESPONSIVE DESIGN
           ===================================================== */
        @media (max-width: 1200px) {
            .metrics-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .execution-section {
                grid-template-columns: 1fr;
            }

            .map-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .dashboard-content {
                padding: 1.5rem;
            }

            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .execution-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .map-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .filter-group select {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
                padding-top: 4rem;
            }

            .dashboard-title {
                padding: 1rem;
                border-radius: 12px;
            }

            .dashboard-title h1 {
                font-size: 1.5rem;
            }

            .dashboard-title p {
                font-size: 0.85rem;
            }

            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .metric-card {
                padding: 1rem;
            }

            .metric-value {
                font-size: 1.5rem;
            }

            .metric-label {
                font-size: 0.8rem;
            }

            .execution-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .execution-card {
                padding: 1rem;
            }

            .execution-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }

            .exec-stat {
                padding: 0.75rem 0.5rem;
            }

            .exec-stat-value {
                font-size: 1.1rem;
            }

            .exec-stat-label {
                font-size: 0.7rem;
            }

            .map-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .dept-ranking {
                max-height: 300px;
            }

            .chart-card {
                padding: 1rem;
            }

            .dep-table {
                min-width: 700px;
            }

            .dep-table th,
            .dep-table td {
                padding: 0.5rem;
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-content {
                padding: 0.75rem;
                padding-top: 4rem;
            }

            .dashboard-title h1 {
                font-size: 1.25rem;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .metric-card {
                padding: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                text-align: left;
            }

            .metric-value {
                font-size: 1.25rem;
                order: 2;
            }

            .metric-label {
                font-size: 0.75rem;
                order: 1;
            }

            .metric-subtitle {
                display: none;
            }

            .execution-stats {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .exec-stat {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.6rem 0.75rem;
            }

            .exec-stat-value {
                font-size: 1.1rem;
                order: 2;
            }

            .exec-stat-label {
                font-size: 0.75rem;
                order: 1;
            }

            .chart-card h3 {
                font-size: 0.9rem;
            }

            .progress-bar-large {
                height: 20px;
            }

            .progress-fill {
                font-size: 0.7rem;
            }

            .dept-item {
                padding: 0.5rem;
            }

            .dept-rank {
                width: 24px;
                height: 24px;
                font-size: 0.7rem;
            }

            .dept-name {
                font-size: 0.85rem;
            }

            .dept-value {
                font-size: 0.9rem;
            }
        }

        /* =====================================================
           LIGHT MODE OVERRIDES
           ===================================================== */
        [data-theme="light"] .metric-card {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(74, 144, 217, 0.20);
            box-shadow: 0 4px 20px rgba(26, 58, 92, 0.08);
        }

        [data-theme="light"] .metric-card .metric-value {
            color: #1a3a5c !important;
        }

        [data-theme="light"] .metric-card.green .metric-value {
            color: #16a34a !important;
        }

        [data-theme="light"] .metric-card.blue .metric-value {
            color: #2563eb !important;
        }

        [data-theme="light"] .metric-card.pink .metric-value {
            color: #db2777 !important;
        }

        [data-theme="light"] .metric-card.orange .metric-value {
            color: #ea580c !important;
        }

        [data-theme="light"] .metric-card.purple .metric-value {
            color: #7c3aed !important;
        }

        [data-theme="light"] .execution-card {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(74, 144, 217, 0.20);
            box-shadow: 0 4px 20px rgba(26, 58, 92, 0.08);
        }

        [data-theme="light"] .execution-card h3 {
            color: #0a1929;
        }

        [data-theme="light"] .exec-stat {
            background: rgba(74, 144, 217, 0.08);
            border: 1px solid rgba(74, 144, 217, 0.15);
        }

        [data-theme="light"] .exec-stat-value {
            color: #1a3a5c;
        }

        [data-theme="light"] .exec-stat-label {
            color: #3d566e;
        }

        /* Estilos tema claro para contenedor Físico */
        [data-theme="light"] .physical-card {
            background: rgba(255, 255, 255, 0.85);
        }

        [data-theme="light"] .physical-row .physical-col {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.4);
        }

        [data-theme="light"] .physical-row .physical-col:nth-child(2) {
            background: rgba(218, 165, 32, 0.15);
            border-color: rgba(218, 165, 32, 0.4);
        }

        [data-theme="light"] .physical-row .physical-col:nth-child(3) {
            background: rgba(37, 99, 235, 0.15);
            border-color: rgba(37, 99, 235, 0.4);
        }

        [data-theme="light"] .row-label {
            color: #1a3a5c;
            text-shadow: none;
        }

        [data-theme="light"] .physical-row .physical-col:first-child .row-value {
            color: #16a34a;
            text-shadow: none;
        }

        [data-theme="light"] .physical-row .physical-col:nth-child(2) .row-value {
            color: #ca8a04;
            text-shadow: none;
        }

        [data-theme="light"] .physical-row .physical-col:nth-child(3) .row-value {
            color: #2563eb;
            text-shadow: none;
        }

        [data-theme="light"] .chart-card {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(74, 144, 217, 0.20);
        }

        [data-theme="light"] .chart-card h3 {
            color: #0a1929;
        }

        [data-theme="light"] .dept-item {
            background: rgba(74, 144, 217, 0.05);
            border: 1px solid rgba(74, 144, 217, 0.12);
        }

        [data-theme="light"] .dept-item:hover {
            background: rgba(74, 144, 217, 0.12);
        }

        [data-theme="light"] .dept-name {
            color: #0a1929;
        }

        [data-theme="light"] .dept-value {
            color: #1a3a5c;
        }

        [data-theme="light"] .filters-bar {
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(74, 144, 217, 0.20);
        }

        [data-theme="light"] .filter-dropdown-trigger {
            background: rgba(255, 255, 255, 0.90);
            border: 1px solid rgba(74, 144, 217, 0.20);
            color: #0a1929;
        }

        [data-theme="light"] .filter-dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(74, 144, 217, 0.20);
        }

        [data-theme="light"] .dep-table-container {
            background: rgba(255, 255, 255, 0.75);
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-title stagger">
                <h1> VIDER </h1>
                <p>Viceministerio de Desarrollo Económico Rural - Sistema de Monitoreo y Evaluación</p>
            </div>

            <div class="filters-bar stagger">
                <div class="filters-header">
                    <div class="filters-title">
                        <i class="fas fa-filter"></i>
                        Filtros Avanzados
                    </div>
                    <div class="filters-actions">
                        <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Aplicar
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Borrar Filtros
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <!-- Dependencia -->
                    <div class="filter-dropdown" data-filter="dependencia">
                        <div class="filter-dropdown-header">
                            <span class="filter-dropdown-label">
                                <i class="fas fa-check-square"></i> Dependencia
                            </span>
                            <div class="filter-dropdown-trigger" onclick="toggleFilterDropdown('dependencia')">
                                <span class="filter-trigger-text" id="dependencia-text">Escriba el término de
                                    búsqueda</span>
                                <span class="filter-trigger-count" id="dependencia-count" style="display:none">0</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-dropdown-menu" id="dependencia-menu">
                            <div class="filter-search-container">
                                <div class="filter-search-wrapper">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="filter-search-input" placeholder="Buscar..."
                                        oninput="filterOptions('dependencia', this.value)">
                                </div>
                            </div>
                            <div class="filter-options-list" id="dependencia-options">
                                <div class="filter-no-results">Cargando...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad -->
                    <div class="filter-dropdown" data-filter="actividad">
                        <div class="filter-dropdown-header">
                            <span class="filter-dropdown-label">
                                <i class="fas fa-check-square"></i> Actividad
                            </span>
                            <div class="filter-dropdown-trigger" onclick="toggleFilterDropdown('actividad')">
                                <span class="filter-trigger-text" id="actividad-text">Escriba el término de
                                    búsqueda</span>
                                <span class="filter-trigger-count" id="actividad-count" style="display:none">0</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-dropdown-menu" id="actividad-menu">
                            <div class="filter-search-container">
                                <div class="filter-search-wrapper">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="filter-search-input" placeholder="Buscar..."
                                        oninput="filterOptions('actividad', this.value)">
                                </div>
                            </div>
                            <div class="filter-options-list" id="actividad-options">
                                <div class="filter-no-results">Cargando...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Producto -->
                    <div class="filter-dropdown" data-filter="producto">
                        <div class="filter-dropdown-header">
                            <span class="filter-dropdown-label">
                                <i class="fas fa-check-square"></i> Producto
                            </span>
                            <div class="filter-dropdown-trigger" onclick="toggleFilterDropdown('producto')">
                                <span class="filter-trigger-text" id="producto-text">Escriba el término de
                                    búsqueda</span>
                                <span class="filter-trigger-count" id="producto-count" style="display:none">0</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-dropdown-menu" id="producto-menu">
                            <div class="filter-search-container">
                                <div class="filter-search-wrapper">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="filter-search-input" placeholder="Buscar..."
                                        oninput="filterOptions('producto', this.value)">
                                </div>
                            </div>
                            <div class="filter-options-list" id="producto-options">
                                <div class="filter-no-results">Cargando...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Intervención -->
                    <div class="filter-dropdown" data-filter="intervencion">
                        <div class="filter-dropdown-header">
                            <span class="filter-dropdown-label">
                                <i class="fas fa-check-square"></i> Intervención
                            </span>
                            <div class="filter-dropdown-trigger" onclick="toggleFilterDropdown('intervencion')">
                                <span class="filter-trigger-text" id="intervencion-text">Escriba el término de
                                    búsqueda</span>
                                <span class="filter-trigger-count" id="intervencion-count" style="display:none">0</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-dropdown-menu" id="intervencion-menu">
                            <div class="filter-search-container">
                                <div class="filter-search-wrapper">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="filter-search-input" placeholder="Buscar..."
                                        oninput="filterOptions('intervencion', this.value)">
                                </div>
                            </div>
                            <div class="filter-options-list" id="intervencion-options">
                                <div class="filter-no-results">Cargando...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="metrics-grid stagger">
                <div class="metric-card green">
                    <div class="metric-value" id="total-beneficiarios">0</div>
                    <div class="metric-label">Total Beneficiarios</div>
                    <div class="metric-subtitle">Personas atendidas</div>
                </div>
                <div class="metric-card blue">
                    <div class="metric-value" id="total-hombres">0</div>
                    <div class="metric-label">Hombres</div>
                    <div class="metric-subtitle" id="pct-hombres">0%</div>
                </div>
                <div class="metric-card pink">
                    <div class="metric-value" id="total-mujeres">0</div>
                    <div class="metric-label">Mujeres</div>
                    <div class="metric-subtitle" id="pct-mujeres">0%</div>
                </div>
                <div class="metric-card orange">
                    <div class="metric-value" id="total-departamentos">22</div>
                    <div class="metric-label">Departamentos</div>
                    <div class="metric-subtitle">Con cobertura</div>
                </div>
                <div class="metric-card purple">
                    <div class="metric-value" id="total-municipios">0</div>
                    <div class="metric-label">Municipios</div>
                    <div class="metric-subtitle">Atendidos</div>
                </div>
            </div>

            <div class="execution-section stagger">
                <div class="execution-card physical-card">
                    <h3><i class="fas fa-tasks"></i> Físico</h3>
                    <div class="physical-table">
                        <div class="physical-header">
                            <div class="physical-col header">Planificado</div>
                            <div class="physical-col header">Ejecutado</div>
                            <div class="physical-col header">Porcentaje</div>
                        </div>
                        <div class="physical-row">
                            <div class="physical-col">
                                <span class="row-label">Personas</span>
                                <span class="row-value" id="plan-personas">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Personas</span>
                                <span class="row-value" id="ejec-personas">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Personas</span>
                                <span class="row-value" id="pct-personas">0,00 %</span>
                            </div>
                        </div>
                        <div class="physical-row">
                            <div class="physical-col">
                                <span class="row-label">Hectáreas</span>
                                <span class="row-value" id="plan-hectareas">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Hectáreas</span>
                                <span class="row-value" id="ejec-hectareas">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Hectáreas</span>
                                <span class="row-value" id="pct-hectareas">0,00 %</span>
                            </div>
                        </div>
                        <div class="physical-row">
                            <div class="physical-col">
                                <span class="row-label">Metros</span>
                                <span class="row-value" id="plan-metros">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Metros</span>
                                <span class="row-value" id="ejec-metros">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Metros</span>
                                <span class="row-value" id="pct-metros">0,00 %</span>
                            </div>
                        </div>
                        <div class="physical-row">
                            <div class="physical-col">
                                <span class="row-label">Metros Cuadrados</span>
                                <span class="row-value" id="plan-m2">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Metros Cuadrados</span>
                                <span class="row-value" id="ejec-m2">0</span>
                            </div>
                            <div class="physical-col">
                                <span class="row-label">Metros Cuadrados</span>
                                <span class="row-value" id="pct-m2">0,00 %</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="execution-card financial-card">
                    <h3><i class="fas fa-dollar-sign"></i> Financiero</h3>
                    <div class="execution-stats">
                        <div class="exec-stat">
                            <div class="exec-stat-value" id="vigente">Q 0</div>
                            <div class="exec-stat-label">Vigente</div>
                        </div>
                        <div class="exec-stat">
                            <div class="exec-stat-value" id="fin-ejecutado">Q 0</div>
                            <div class="exec-stat-label">Ejecutado</div>
                        </div>
                        <div class="exec-stat">
                            <div class="exec-stat-value" id="pct-financiero">0%</div>
                            <div class="exec-stat-label">% Avance</div>
                        </div>
                    </div>
                    <div class="progress-bar-large">
                        <div class="progress-fill financial" id="progress-financial" style="width: 0%">0%</div>
                    </div>
                </div>
            </div>

            <div class="chart-card stagger">
                <h3><i class="fas fa-building"></i> Resumen por Dependencia</h3>
                <div class="table-responsive">
                    <table class="dep-table">
                        <thead>
                            <tr>
                                <th>Dependencia</th>
                                <th>Beneficiarios</th>
                                <th>Hombres</th>
                                <th>Mujeres</th>
                                <th>Programado</th>
                                <th>Ejecutado</th>
                                <th>% Ejec.</th>
                                <th>Financiero Q</th>
                            </tr>
                        </thead>
                        <tbody id="dep-table-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="charts-grid stagger">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Beneficiarios por Departamento</h3>
                    <canvas id="chart-departamentos" height="300"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Distribución por Género</h3>
                    <canvas id="chart-genero" height="300"></canvas>
                </div>
            </div>

            <div class="map-section stagger">
                <div class="chart-card">
                    <h3><i class="fas fa-map"></i> Mapa de Guatemala - Cobertura VIDER</h3>
                    <div id="map-container" style="height: 450px; position: relative;"></div>
                </div>
                <div class="dept-ranking">
                    <h3 style="font-family: var(--font-display); margin-bottom: 1rem;">
                        <i class="fas fa-trophy"></i> Top Departamentos
                    </h3>
                    <div id="dept-ranking-list"></div>
                </div>
            </div>

            <div class="charts-grid stagger">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Ejecución por Dependencia</h3>
                    <canvas id="chart-dependencias" height="250"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Distribución de Beneficiarios</h3>
                    <canvas id="chart-dep-pie" height="250"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let dashboardData = null;

        document.addEventListener('DOMContentLoaded', function () {
            initMobileMenu();
            loadFilters(); // Cargar filtros inmediatamente
            loadDashboardData();
            createGuatemalaMap();
        });

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

            // Close sidebar when clicking a nav link on mobile
            sidebar.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024) {
                    closeSidebar();
                }
            });
        }

        async function loadDashboardData(filtros = {}) {
            try {
                // Construir URL con parámetros de filtro
                const params = new URLSearchParams();
                if (filtros.departamento) params.append('departamento', filtros.departamento);
                if (filtros.dependencia) params.append('dependencia', filtros.dependencia);
                if (filtros.actividad) params.append('actividad', filtros.actividad);
                if (filtros.producto) params.append('producto', filtros.producto);
                if (filtros.intervencion) params.append('intervencion', filtros.intervencion);

                const hasFilters = params.toString().length > 0;

                // Intentar usar cache primero (solo si no hay filtros activos)
                if (!hasFilters) {
                    const cachedData = getCachedData();
                    if (cachedData) {
                        dashboardData = cachedData;
                        updateMetrics(dashboardData);
                        updateDepTable(dashboardData.por_dependencia);

                        // Destruir gráficos existentes antes de crear nuevos
                        Chart.getChart('chart-departamentos')?.destroy();
                        Chart.getChart('chart-genero')?.destroy();
                        Chart.getChart('chart-dependencias')?.destroy();
                        Chart.getChart('chart-dep-pie')?.destroy();

                        createCharts(dashboardData);
                        updateDeptRanking(dashboardData.por_departamento);

                        console.log('📦 Datos cargados desde cache');
                        return;
                    }
                }

                const url = 'api/get_dashboard_stats.php' + (hasFilters ? '?' + params.toString() : '');
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    dashboardData = result.data;

                    // Guardar en cache si es consulta sin filtros
                    if (!hasFilters) {
                        setCachedData(dashboardData);
                    }

                    updateMetrics(dashboardData);
                    updateDepTable(dashboardData.por_dependencia);

                    // Destruir gráficos existentes antes de crear nuevos
                    Chart.getChart('chart-departamentos')?.destroy();
                    Chart.getChart('chart-genero')?.destroy();
                    Chart.getChart('chart-dependencias')?.destroy();
                    Chart.getChart('chart-dep-pie')?.destroy();

                    createCharts(dashboardData);
                    updateDeptRanking(dashboardData.por_departamento);

                    // Solo cargar filtros la primera vez
                    if (!hasFilters) {
                        loadFilters();
                    }

                    if (hasFilters) {
                        showToast('Datos filtrados correctamente', 'success');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                loadSampleData();
            }
        }

        function loadSampleData() {
            const sampleData = {
                total_beneficiarios: 95424,
                total_hombres: 33199,
                total_mujeres: 62225,
                total_programado: 909530,
                total_ejecutado: 223808,
                total_vigente: 425944026.60,
                total_financiero_ejecutado: 55380509.18,
                total_departamentos: 22,
                total_municipios: 327,
                // Physical execution data
                fisico: {
                    personas: { planificado: 344417, ejecutado: 95319 },
                    hectareas: { planificado: 273, ejecutado: 0 },
                    metros: { planificado: 104792, ejecutado: 16585 },
                    m2: { planificado: 7166, ejecutado: 0 }
                },
                por_dependencia: [
                    { siglas: 'DIREPRO', beneficiarios: 82161, hombres: 26352, mujeres: 55809, programado: 84288, ejecutado: 82161, vigente: 48816342.57 },
                    { siglas: 'DDP', beneficiarios: 5548, hombres: 2641, mujeres: 2907, programado: 10852, ejecutado: 5548, vigente: 2868557.62 },
                    { siglas: 'DIFOPROCO', beneficiarios: 5111, hombres: 2705, mujeres: 2406, programado: 10000, ejecutado: 5011, vigente: 2886804.16 },
                    { siglas: 'DDA', beneficiarios: 1474, hombres: 762, mujeres: 712, programado: 234671, ejecutado: 1469, vigente: 182401676.00 },
                    { siglas: 'DIPRODU', beneficiarios: 1130, hombres: 739, mujeres: 391, programado: 116837, ejecutado: 17715, vigente: 188970646.25 }
                ],
                por_departamento: {
                    'Alta Verapaz': { beneficiarios: 11775, hombres: 4120, mujeres: 7655 },
                    'Chiquimula': { beneficiarios: 10053, hombres: 3519, mujeres: 6534 },
                    'Izabal': { beneficiarios: 6970, hombres: 2440, mujeres: 4530 },
                    'Zacapa': { beneficiarios: 6626, hombres: 2319, mujeres: 4307 },
                    'Retalhuleu': { beneficiarios: 5603, hombres: 1961, mujeres: 3642 },
                    'Jalapa': { beneficiarios: 5239, hombres: 1834, mujeres: 3405 },
                    'Jutiapa': { beneficiarios: 5147, hombres: 1801, mujeres: 3346 },
                    'Huehuetenango': { beneficiarios: 4604, hombres: 1611, mujeres: 2993 },
                    'Quiché': { beneficiarios: 4152, hombres: 1453, mujeres: 2699 },
                    'Santa Rosa': { beneficiarios: 4096, hombres: 1434, mujeres: 2662 },
                    'San Marcos': { beneficiarios: 3850, hombres: 1348, mujeres: 2502 },
                    'Petén': { beneficiarios: 3720, hombres: 1302, mujeres: 2418 },
                    'Suchitepéquez': { beneficiarios: 3500, hombres: 1225, mujeres: 2275 },
                    'Escuintla': { beneficiarios: 3200, hombres: 1120, mujeres: 2080 },
                    'Quetzaltenango': { beneficiarios: 2900, hombres: 1015, mujeres: 1885 },
                    'Baja Verapaz': { beneficiarios: 2800, hombres: 980, mujeres: 1820 },
                    'Chimaltenango': { beneficiarios: 2600, hombres: 910, mujeres: 1690 },
                    'Sololá': { beneficiarios: 2400, hombres: 840, mujeres: 1560 },
                    'Totonicapán': { beneficiarios: 2200, hombres: 770, mujeres: 1430 },
                    'El Progreso': { beneficiarios: 1800, hombres: 630, mujeres: 1170 },
                    'Sacatepéquez': { beneficiarios: 1500, hombres: 525, mujeres: 975 },
                    'Guatemala': { beneficiarios: 1289, hombres: 451, mujeres: 838 }
                }
            };
            updateMetrics(sampleData);
            updateDepTable(sampleData.por_dependencia);
            createCharts(sampleData);
            updateDeptRanking(sampleData.por_departamento);
        }

        function updateMetrics(data) {
            animateValue('total-beneficiarios', data.total_beneficiarios);
            animateValue('total-hombres', data.total_hombres);
            animateValue('total-mujeres', data.total_mujeres);
            animateValue('total-municipios', data.total_municipios);

            document.getElementById('pct-hombres').textContent = ((data.total_hombres / data.total_beneficiarios) * 100).toFixed(1) + '%';
            document.getElementById('pct-mujeres').textContent = ((data.total_mujeres / data.total_beneficiarios) * 100).toFixed(1) + '%';

            // Update Physical Execution Table
            if (data.fisico) {
                const f = data.fisico;

                // Personas
                document.getElementById('plan-personas').textContent = f.personas.planificado.toLocaleString('es-GT');
                document.getElementById('ejec-personas').textContent = f.personas.ejecutado.toLocaleString('es-GT');
                const pctPersonas = f.personas.planificado > 0 ? ((f.personas.ejecutado / f.personas.planificado) * 100).toFixed(2) : 0;
                document.getElementById('pct-personas').textContent = pctPersonas + ' %';

                // Hectáreas
                document.getElementById('plan-hectareas').textContent = f.hectareas.planificado.toLocaleString('es-GT');
                document.getElementById('ejec-hectareas').textContent = f.hectareas.ejecutado.toLocaleString('es-GT');
                const pctHectareas = f.hectareas.planificado > 0 ? ((f.hectareas.ejecutado / f.hectareas.planificado) * 100).toFixed(2) : 0;
                document.getElementById('pct-hectareas').textContent = pctHectareas + ' %';

                // Metros
                document.getElementById('plan-metros').textContent = f.metros.planificado.toLocaleString('es-GT');
                document.getElementById('ejec-metros').textContent = f.metros.ejecutado.toLocaleString('es-GT');
                const pctMetros = f.metros.planificado > 0 ? ((f.metros.ejecutado / f.metros.planificado) * 100).toFixed(2) : 0;
                document.getElementById('pct-metros').textContent = pctMetros + ' %';

                // Metros Cuadrados
                document.getElementById('plan-m2').textContent = f.m2.planificado.toLocaleString('es-GT');
                document.getElementById('ejec-m2').textContent = f.m2.ejecutado.toLocaleString('es-GT');
                const pctM2 = f.m2.planificado > 0 ? ((f.m2.ejecutado / f.m2.planificado) * 100).toFixed(2) : 0;
                document.getElementById('pct-m2').textContent = pctM2 + ' %';
            }

            // Update Financial Card
            document.getElementById('vigente').textContent = 'Q ' + formatMillions(data.total_vigente);
            document.getElementById('fin-ejecutado').textContent = 'Q ' + formatMillions(data.total_financiero_ejecutado);
            const pctFinanciero = ((data.total_financiero_ejecutado / data.total_vigente) * 100).toFixed(1);
            document.getElementById('pct-financiero').textContent = pctFinanciero + '%';

            setTimeout(() => {
                document.getElementById('progress-financial').style.width = pctFinanciero + '%';
                document.getElementById('progress-financial').textContent = pctFinanciero + '%';
            }, 500);
        }

        function formatMillions(num) {
            return num >= 1000000 ? (num / 1000000).toFixed(2) + 'M' : num.toLocaleString('es-GT');
        }

        function updateDepTable(deps) {
            const tbody = document.getElementById('dep-table-body');
            const badgeClasses = ['direpro', 'ddp', 'difoproco', 'dda', 'diprodu'];
            tbody.innerHTML = deps.map((dep, i) => {
                const pctEjec = dep.programado > 0 ? ((dep.ejecutado / dep.programado) * 100).toFixed(1) : 0;
                return `<tr>
                    <td><span class="dep-badge ${badgeClasses[i] || 'dda'}">${dep.siglas}</span></td>
                    <td><strong>${dep.beneficiarios.toLocaleString('es-GT')}</strong></td>
                    <td>${dep.hombres.toLocaleString('es-GT')}</td>
                    <td>${dep.mujeres.toLocaleString('es-GT')}</td>
                    <td>${dep.programado.toLocaleString('es-GT')}</td>
                    <td>${dep.ejecutado.toLocaleString('es-GT')}</td>
                    <td>${pctEjec}%</td>
                    <td>Q ${formatMillions(dep.vigente)}</td>
                </tr>`;
            }).join('');
        }

        function createCharts(data) {
            const deptData = Object.entries(data.por_departamento).sort((a, b) => b[1].beneficiarios - a[1].beneficiarios).slice(0, 12);

            // Detect current theme - check both DOM and localStorage
            const savedTheme = localStorage.getItem('vider-theme');
            const domTheme = document.documentElement.getAttribute('data-theme');
            const isLightMode = savedTheme === 'light' || domTheme === 'light';
            const textColor = isLightMode ? '#1a3a5c' : '#e2e8f0';
            const gridColor = isLightMode ? 'rgba(26, 58, 92, 0.1)' : 'rgba(255, 255, 255, 0.1)';

            new Chart(document.getElementById('chart-departamentos'), {
                type: 'bar',
                data: {
                    labels: deptData.map(d => d[0]),
                    datasets: [{ label: 'Beneficiarios', data: deptData.map(d => d[1].beneficiarios), backgroundColor: 'rgba(74, 144, 217, 0.8)', borderRadius: 6 }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor } },
                        y: { grid: { display: false }, ticks: { color: textColor } }
                    }
                }
            });

            new Chart(document.getElementById('chart-genero'), {
                type: 'doughnut',
                data: { labels: ['Mujeres', 'Hombres'], datasets: [{ data: [data.total_mujeres, data.total_hombres], backgroundColor: ['#ec4899', '#3b82f6'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { color: textColor } } } }
            });

            const depColors = ['#8b5cf6', '#3b82f6', '#ec4899', '#1a3a5c', '#f97316'];
            new Chart(document.getElementById('chart-dependencias'), {
                type: 'bar',
                data: {
                    labels: data.por_dependencia.map(d => d.siglas),
                    datasets: [
                        { label: 'Programado', data: data.por_dependencia.map(d => d.programado), backgroundColor: 'rgba(148, 163, 184, 0.5)', borderRadius: 4 },
                        { label: 'Ejecutado', data: data.por_dependencia.map(d => d.ejecutado), backgroundColor: depColors, borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { color: textColor } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor } },
                        y: { grid: { color: gridColor }, ticks: { color: textColor } }
                    }
                }
            });

            new Chart(document.getElementById('chart-dep-pie'), {
                type: 'pie',
                data: { labels: data.por_dependencia.map(d => d.siglas), datasets: [{ data: data.por_dependencia.map(d => d.beneficiarios), backgroundColor: depColors, borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: textColor } } } }
            });
        }

        function updateDeptRanking(depts) {
            const container = document.getElementById('dept-ranking-list');
            const sorted = Object.entries(depts).sort((a, b) => b[1].beneficiarios - a[1].beneficiarios);
            container.innerHTML = sorted.map(([name, data], i) => `
                <div class="dept-item" onclick="selectDepartment('${name}')">
                    <div class="dept-rank">${i + 1}</div>
                    <div class="dept-info">
                        <div class="dept-name">${name}</div>
                        <div class="dept-stats">
                            <i class="fas fa-male" style="color: #3b82f6;"></i> ${data.hombres.toLocaleString('es-GT')}
                            <i class="fas fa-female" style="color: #ec4899; margin-left: 0.5rem;"></i> ${data.mujeres.toLocaleString('es-GT')}
                        </div>
                    </div>
                    <div class="dept-value">${data.beneficiarios.toLocaleString('es-GT')}</div>
                </div>
            `).join('');
        }

        function animateValue(elementId, endValue, duration = 1500) {
            const element = document.getElementById(elementId);
            const startTime = performance.now();
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(endValue * (1 - Math.pow(1 - progress, 3)));
                element.textContent = current.toLocaleString('es-GT');
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }

        // Estado de los filtros con selecciones múltiples
        const filterSelections = {
            dependencia: [],
            actividad: [],
            producto: [],
            intervencion: []
        };

        // Datos de catálogos cargados
        let filterCatalogs = {
            dependencias: [],
            actividades: [],
            productos: [],
            intervenciones: []
        };

        // =====================================================
        // FUNCIONALIDAD: Filtros Persistentes (localStorage)
        // =====================================================
        const FILTER_STORAGE_KEY = 'vider_filter_selections';
        const DATA_CACHE_KEY = 'vider_dashboard_cache';
        const CACHE_EXPIRY_MS = 5 * 60 * 1000; // 5 minutos

        function saveFiltersToStorage() {
            localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(filterSelections));
            showNotification('Filtros guardados', 'success');
        }

        function loadFiltersFromStorage() {
            try {
                const saved = localStorage.getItem(FILTER_STORAGE_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    Object.assign(filterSelections, parsed);
                    return true;
                }
            } catch (e) {
                console.error('Error loading filters:', e);
            }
            return false;
        }

        function clearSavedFilters() {
            localStorage.removeItem(FILTER_STORAGE_KEY);
            Object.keys(filterSelections).forEach(key => filterSelections[key] = []);
            showNotification('Filtros limpiados', 'info');
            location.reload();
        }

        // =====================================================
        // FUNCIONALIDAD: Cache de Datos
        // =====================================================
        function getCachedData() {
            try {
                const cached = localStorage.getItem(DATA_CACHE_KEY);
                if (cached) {
                    const { data, timestamp } = JSON.parse(cached);
                    if (Date.now() - timestamp < CACHE_EXPIRY_MS) {
                        return data;
                    }
                }
            } catch (e) {
                console.error('Error reading cache:', e);
            }
            return null;
        }

        function setCachedData(data) {
            try {
                localStorage.setItem(DATA_CACHE_KEY, JSON.stringify({
                    data: data,
                    timestamp: Date.now()
                }));
            } catch (e) {
                console.error('Error saving cache:', e);
            }
        }

        function clearCache() {
            localStorage.removeItem(DATA_CACHE_KEY);
            showNotification('Cache limpiado', 'info');
        }

        // =====================================================
        // FUNCIONALIDAD: Notificaciones Toast
        // =====================================================
        function showNotification(message, type = 'info', duration = 3000) {
            // Crear contenedor si no existe
            let container = document.getElementById('notification-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notification-container';
                container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:10px;';
                document.body.appendChild(container);
            }

            const colors = {
                success: 'linear-gradient(135deg, #22c55e, #16a34a)',
                error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
            };

            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            const toast = document.createElement('div');
            toast.style.cssText = `
                background: ${colors[type] || colors.info};
                color: white;
                padding: 12px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 0.9rem;
                animation: slideInRight 0.3s ease;
                cursor: pointer;
            `;
            toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${message}`;
            toast.onclick = () => toast.remove();

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Añadir estilos de animación para notificaciones
        const notifStyles = document.createElement('style');
        notifStyles.textContent = `
            @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        `;
        document.head.appendChild(notifStyles);

        function loadFilters() {
            fetch('api/get_filter_catalogs.php').then(r => r.json()).then(result => {
                if (result.success && result.data) {
                    filterCatalogs = result.data;
                    renderFilterOptions('dependencia', result.data.dependencias, 'id', 'nombre');
                    renderFilterOptions('actividad', result.data.actividades, 'id', 'nombre');
                    renderFilterOptions('producto', result.data.productos, 'id', 'nombre');
                    renderFilterOptions('intervencion', result.data.intervenciones, 'id', 'nombre');
                }
            }).catch(err => {
                console.error('Error loading filters:', err);
            });
        }

        function renderFilterOptions(filterName, items, valueField, labelField) {
            const container = document.getElementById(`${filterName}-options`);
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="filter-no-results">Sin opciones disponibles</div>';
                return;
            }

            // Check if all items are selected
            const allSelected = items.every(item => filterSelections[filterName].includes(String(item[valueField])));

            // Build Select All checkbox + all options
            let html = `
                <label class="filter-option select-all-option ${allSelected ? 'selected' : ''}" data-value="__all__">
                    <input type="checkbox" ${allSelected ? 'checked' : ''} onchange="toggleSelectAll('${filterName}', this.checked)">
                    <span class="filter-option-label"><strong>Seleccionar todos</strong></span>
                </label>
            `;

            html += items.map(item => {
                const value = item[valueField];
                const label = item[labelField] || value;
                const isChecked = filterSelections[filterName].includes(String(value));
                return `
                    <label class="filter-option ${isChecked ? 'selected' : ''}" data-value="${value}" data-label="${label}">
                        <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleFilterOption('${filterName}', '${value}', '${label.replace(/'/g, "\\'")}', this.checked)">
                        <span class="filter-option-label" title="${label}">${label}</span>
                    </label>
                `;
            }).join('');

            container.innerHTML = html;
        }

        function toggleSelectAll(filterName, isChecked) {
            const container = document.getElementById(`${filterName}-options`);
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            const options = container.querySelectorAll('.filter-option:not(.select-all-option)');

            // Clear or fill selections
            if (isChecked) {
                // Select all - get all values from the options
                filterSelections[filterName] = [];
                options.forEach(option => {
                    const value = option.dataset.value;
                    if (value && value !== '__all__') {
                        filterSelections[filterName].push(String(value));
                    }
                });
            } else {
                // Deselect all
                filterSelections[filterName] = [];
            }

            // Update all checkboxes visual state
            checkboxes.forEach(cb => {
                if (cb.closest('.filter-option').dataset.value !== '__all__') {
                    cb.checked = isChecked;
                    cb.closest('.filter-option').classList.toggle('selected', isChecked);
                }
            });

            updateFilterDisplay(filterName);

            // FILTRADO EN CASCADA: Si cambia dependencia, recargar otros filtros
            if (filterName === 'dependencia') {
                reloadCascadeFilters();
            }
        }

        function toggleFilterDropdown(filterName) {
            const menu = document.getElementById(`${filterName}-menu`);
            const trigger = menu.previousElementSibling?.querySelector('.filter-dropdown-trigger') ||
                document.querySelector(`[data-filter="${filterName}"] .filter-dropdown-trigger`);

            // Cerrar otros dropdowns
            document.querySelectorAll('.filter-dropdown-menu.show').forEach(m => {
                if (m.id !== `${filterName}-menu`) {
                    m.classList.remove('show');
                    const otherTrigger = m.closest('.filter-dropdown').querySelector('.filter-dropdown-trigger');
                    if (otherTrigger) otherTrigger.classList.remove('active');
                }
            });

            menu.classList.toggle('show');
            if (trigger) trigger.classList.toggle('active', menu.classList.contains('show'));
        }

        function toggleFilterOption(filterName, value, label, isChecked) {
            const selections = filterSelections[filterName];
            const valueStr = String(value);

            if (isChecked) {
                if (!selections.includes(valueStr)) {
                    selections.push(valueStr);
                }
            } else {
                const idx = selections.indexOf(valueStr);
                if (idx > -1) selections.splice(idx, 1);
            }

            updateFilterDisplay(filterName);

            // Actualizar clase selected en el option
            const option = document.querySelector(`#${filterName}-options [data-value="${value}"]`);
            if (option) option.classList.toggle('selected', isChecked);

            // Sync Select All checkbox state
            updateSelectAllState(filterName);

            // FILTRADO EN CASCADA: Si cambia dependencia, recargar otros filtros
            if (filterName === 'dependencia') {
                reloadCascadeFilters();
            }
        }

        // Función para recargar filtros en cascada
        function reloadCascadeFilters() {
            const dependencias = filterSelections['dependencia'];
            const url = dependencias.length > 0 
                ? `api/get_filter_catalogs.php?dependencia=${dependencias.join(',')}`
                : 'api/get_filter_catalogs.php';

            // Mostrar indicador de carga en los filtros afectados
            ['actividad', 'producto', 'intervencion'].forEach(filterName => {
                const container = document.getElementById(`${filterName}-options`);
                if (container) {
                    container.innerHTML = '<div class="filter-loading"><i class="fas fa-spinner fa-spin"></i> Filtrando...</div>';
                }
            });

            fetch(url)
                .then(r => r.json())
                .then(result => {
                    if (result.success && result.data) {
                        // Limpiar selecciones de filtros dependientes que ya no existen
                        cleanInvalidSelections('actividad', result.data.actividades, 'id');
                        cleanInvalidSelections('producto', result.data.productos, 'id');
                        cleanInvalidSelections('intervencion', result.data.intervenciones, 'id');

                        // Recargar opciones
                        renderFilterOptions('actividad', result.data.actividades, 'id', 'nombre');
                        renderFilterOptions('producto', result.data.productos, 'id', 'nombre');
                        renderFilterOptions('intervencion', result.data.intervenciones, 'id', 'nombre');
                    }
                })
                .catch(err => console.error('Error en filtrado cascada:', err));
        }

        // Limpiar selecciones inválidas después del filtrado
        function cleanInvalidSelections(filterName, validItems, valueField) {
            const validIds = validItems.map(item => String(item[valueField]));
            filterSelections[filterName] = filterSelections[filterName].filter(id => validIds.includes(id));
            updateFilterDisplay(filterName);
        }

        function updateSelectAllState(filterName) {
            const container = document.getElementById(`${filterName}-options`);
            const selectAllOption = container.querySelector('.select-all-option');
            if (!selectAllOption) return;

            const selectAllCheckbox = selectAllOption.querySelector('input[type="checkbox"]');
            const allOptions = container.querySelectorAll('.filter-option:not(.select-all-option)');
            const checkedOptions = container.querySelectorAll('.filter-option:not(.select-all-option) input:checked');

            const allChecked = allOptions.length > 0 && allOptions.length === checkedOptions.length;
            selectAllCheckbox.checked = allChecked;
            selectAllOption.classList.toggle('selected', allChecked);
        }

        function updateFilterDisplay(filterName) {
            const selections = filterSelections[filterName];
            const textEl = document.getElementById(`${filterName}-text`);
            const countEl = document.getElementById(`${filterName}-count`);

            if (selections.length === 0) {
                textEl.textContent = 'Escriba el término de búsqueda';
                textEl.classList.remove('has-selection');
                countEl.style.display = 'none';
            } else if (selections.length === 1) {
                // Encontrar el label del item seleccionado
                const option = document.querySelector(`#${filterName}-options [data-value="${selections[0]}"]`);
                textEl.textContent = option ? option.dataset.label : selections[0];
                textEl.classList.add('has-selection');
                countEl.style.display = 'none';
            } else {
                textEl.textContent = `${selections.length} seleccionados`;
                textEl.classList.add('has-selection');
                countEl.textContent = selections.length;
                countEl.style.display = 'inline-block';
            }
        }

        function filterOptions(filterName, searchTerm) {
            const container = document.getElementById(`${filterName}-options`);
            const options = container.querySelectorAll('.filter-option');
            const term = searchTerm.toLowerCase().trim();
            let visibleCount = 0;

            options.forEach(option => {
                const label = option.dataset.label.toLowerCase();
                const matches = label.includes(term);
                option.style.display = matches ? 'flex' : 'none';
                if (matches) visibleCount++;
            });

            // Mostrar mensaje si no hay resultados
            let noResults = container.querySelector('.filter-no-results');
            if (visibleCount === 0 && !noResults) {
                noResults = document.createElement('div');
                noResults.className = 'filter-no-results';
                noResults.textContent = 'No se encontraron resultados';
                container.appendChild(noResults);
            } else if (noResults && visibleCount > 0) {
                noResults.remove();
            }
        }

        function applyFilters() {
            const filtros = {};

            // Recopilar todas las selecciones
            if (filterSelections.dependencia.length > 0) {
                filtros.dependencia = filterSelections.dependencia.join(',');
            }
            if (filterSelections.actividad.length > 0) {
                filtros.actividad = filterSelections.actividad.join(',');
            }
            if (filterSelections.producto.length > 0) {
                filtros.producto = filterSelections.producto.join(',');
            }
            if (filterSelections.intervencion.length > 0) {
                filtros.intervencion = filterSelections.intervencion.join(',');
            }

            const hasFilters = Object.keys(filtros).length > 0;

            if (hasFilters) {
                showToast('Aplicando filtros...', 'info');
            } else {
                showToast('Mostrando todos los datos', 'info');
            }

            // Cerrar todos los dropdowns
            document.querySelectorAll('.filter-dropdown-menu.show').forEach(m => m.classList.remove('show'));
            document.querySelectorAll('.filter-dropdown-trigger.active').forEach(t => t.classList.remove('active'));

            loadDashboardData(filtros);
        }

        function clearFilters() {
            // Limpiar todas las selecciones
            filterSelections.dependencia = [];
            filterSelections.actividad = [];
            filterSelections.producto = [];
            filterSelections.intervencion = [];

            // Actualizar displays y UI para cada filtro
            ['dependencia', 'actividad', 'producto', 'intervencion'].forEach(name => {
                // Actualizar texto del trigger
                const textEl = document.getElementById(`${name}-text`);
                const countEl = document.getElementById(`${name}-count`);
                if (textEl) {
                    textEl.textContent = 'Escriba el término de búsqueda';
                    textEl.classList.remove('has-selection');
                }
                if (countEl) {
                    countEl.style.display = 'none';
                }

                // Desmarcar todos los checkboxes
                const container = document.getElementById(`${name}-options`);
                if (container) {
                    container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                    });
                    container.querySelectorAll('.filter-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                }

                // Limpiar búsqueda
                const searchInput = document.querySelector(`#${name}-menu .filter-search-input`);
                if (searchInput) {
                    searchInput.value = '';
                }

                // Mostrar todas las opciones (quitar filtro de búsqueda)
                if (container) {
                    container.querySelectorAll('.filter-option').forEach(opt => {
                        opt.style.display = '';
                    });
                }
            });

            // Cerrar todos los dropdowns abiertos
            document.querySelectorAll('.filter-dropdown-menu.show').forEach(m => m.classList.remove('show'));
            document.querySelectorAll('.filter-dropdown-trigger.active').forEach(t => t.classList.remove('active'));

            // Recargar todos los catálogos sin restricciones
            loadFilters();

            showToast('Filtros limpiados', 'success');
            
            // Recargar datos del dashboard sin filtros
            loadDashboardData({});
        }

        function selectDepartment(name) {
            // Esta función ya no aplica con los nuevos filtros
            showToast(`Departamento: ${name}`, 'info');
        }

        // Cerrar dropdowns al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.filter-dropdown')) {
                document.querySelectorAll('.filter-dropdown-menu.show').forEach(m => m.classList.remove('show'));
                document.querySelectorAll('.filter-dropdown-trigger.active').forEach(t => t.classList.remove('active'));
            }
        });

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i><span>${message}</span>`;
            let container = document.querySelector('.toast-container');
            if (!container) { container = document.createElement('div'); container.className = 'toast-container'; document.body.appendChild(container); }
            container.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3000);
        }

        function createGuatemalaMap() {
            document.getElementById('map-container').innerHTML = `
                <svg viewBox="0 0 500 500" style="width: 100%; height: 100%;">
                    <g transform="translate(50, 20) scale(0.8)">
                        <path class="dept-path" data-name="Petén" d="M150,30 L280,30 L300,80 L280,150 L200,180 L120,150 L100,80 Z" fill="#4ade80" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Huehuetenango" d="M50,150 L120,150 L140,200 L100,250 L30,220 Z" fill="#22c55e" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Quiché" d="M120,150 L200,180 L200,250 L140,260 L100,250 L140,200 Z" fill="#16a34a" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Alta Verapaz" d="M200,180 L280,150 L320,200 L280,280 L200,250 Z" fill="#15803d" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Izabal" d="M280,150 L380,130 L400,180 L350,220 L320,200 Z" fill="#166534" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="San Marcos" d="M30,220 L100,250 L90,310 L20,290 Z" fill="#4ade80" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Quetzaltenango" d="M90,310 L100,250 L140,260 L150,320 L100,340 Z" fill="#22c55e" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Totonicapán" d="M140,260 L170,260 L180,300 L150,320 Z" fill="#16a34a" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Sololá" d="M140,320 L150,320 L180,300 L190,340 L160,360 Z" fill="#15803d" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Chimaltenango" d="M190,340 L180,300 L220,290 L240,340 L210,360 Z" fill="#166534" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Baja Verapaz" d="M200,250 L280,280 L280,320 L220,290 L180,300 L170,260 Z" fill="#4ade80" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="El Progreso" d="M280,280 L320,260 L340,300 L300,330 L280,320 Z" fill="#22c55e" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Zacapa" d="M320,200 L350,220 L380,260 L340,300 L320,260 Z" fill="#16a34a" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Chiquimula" d="M340,300 L380,260 L420,300 L400,350 L360,340 Z" fill="#15803d" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Jalapa" d="M300,330 L340,300 L360,340 L340,380 L300,370 Z" fill="#166534" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Jutiapa" d="M340,380 L360,340 L400,350 L420,400 L380,420 Z" fill="#4ade80" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Santa Rosa" d="M260,380 L300,370 L340,380 L320,420 L270,410 Z" fill="#22c55e" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Guatemala" d="M220,290 L280,320 L300,370 L260,380 L240,340 Z" fill="#16a34a" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Sacatepéquez" d="M210,360 L240,340 L260,380 L230,390 Z" fill="#15803d" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Escuintla" d="M160,360 L210,360 L230,390 L260,380 L270,410 L200,430 L140,400 Z" fill="#166534" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Suchitepéquez" d="M100,340 L160,360 L140,400 L80,390 Z" fill="#4ade80" stroke="#fff" stroke-width="1"/>
                        <path class="dept-path" data-name="Retalhuleu" d="M20,290 L90,310 L100,340 L80,390 L30,370 Z" fill="#22c55e" stroke="#fff" stroke-width="1"/>
                    </g>
                </svg>
                <div id="map-tooltip" class="map-tooltip" style="display: none;"></div>`;

            document.querySelectorAll('.dept-path').forEach(path => {
                path.addEventListener('mouseenter', function (e) {
                    const tooltip = document.getElementById('map-tooltip');
                    tooltip.innerHTML = `<strong>${this.dataset.name}</strong>`;
                    tooltip.style.display = 'block';
                    tooltip.style.left = (e.offsetX + 10) + 'px';
                    tooltip.style.top = (e.offsetY + 10) + 'px';
                });
                path.addEventListener('mouseleave', () => { document.getElementById('map-tooltip').style.display = 'none'; });
                path.addEventListener('click', function () { selectDepartment(this.dataset.name); });
            });
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>