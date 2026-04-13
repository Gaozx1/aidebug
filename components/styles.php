<?php

function renderStyles() {
    ?>
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-light: #f8f9fa;
            --text-dark: #343a40;
            --border-color: #dee2e6;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dark-mode {
            --light-bg: #2d3748;
            --dark-bg: #1a202c;
            --text-light: #f7fafc;
            --text-dark: #e2e8f0;
            --border-color: #4a5568;
            --shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }


        .sidebar {
            width: 280px;
            background: white;
            box-shadow: var(--shadow);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .dark-mode .sidebar {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .sidebar-header h1 {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 10px;
        }

        .user-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .points-display {
            background: var(--success-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }

        .sidebar-section {
            margin-bottom: 30px;
        }

        .sidebar-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 16px;
            border-left: 3px solid var(--primary-color);
            padding-left: 10px;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 8px;
        }

        .sidebar-nav a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--text-dark);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .dark-mode .sidebar-nav a {
            color: var(--text-light);
        }

        .sidebar-nav a:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .dark-mode .sidebar-nav a:hover {
            background: #4a5568;
        }

        .sidebar-nav a.active {
            background: var(--primary-color);
            color: white;
        }


        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .theme-toggle {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .message.success {
            background: #d4edda;
            border-color: var(--success-color);
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border-color: var(--danger-color);
            color: #721c24;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }
        }


        .tab-buttons {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .tab-button {
            padding: 10px 20px;
            border: none;
            background: var(--light-bg);
            color: var(--text-dark);
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .dark-mode .tab-button {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .tab-button:hover {
            background: var(--primary-color);
            color: white;
        }

        .tab-button.active {
            background: var(--primary-color);
            color: white;
            border-bottom: 2px solid var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }


        .config-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .dark-mode .form-group label {
            color: var(--text-light);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: var(--light-bg);
            color: var(--text-dark);
            font-size: 14px;
        }

        .dark-mode .form-group input,
        .dark-mode .form-group select,
        .dark-mode .form-group textarea {
            background: var(--dark-bg);
            color: var(--text-light);
            border-color: var(--border-color);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }


        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .dark-mode .stat-card {
            background: var(--dark-bg);
            color: var(--text-light);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-dark);
            font-size: 14px;
        }

        .dark-mode .stat-label {
            color: var(--text-light);
        }


        .code-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .code-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }

        .dark-mode .code-item {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }

        .code-info {
            flex: 1;
        }

        .code-value {
            font-family: monospace;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .status-active {
            background: var(--success-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        .status-used {
            background: var(--danger-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }


        .test-section {
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .dark-mode .test-section {
            background: var(--dark-bg);
            border-color: var(--border-color);
        }

        .test-section h4 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .test-section ul {
            margin: 0;
            padding-left: 20px;
        }

        .test-section li {
            margin-bottom: 5px;
        }


        .admin-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .dark-mode .admin-section {
            background: var(--dark-bg);
            color: var(--text-light);
        }
    </style>
    <?php
}
?>