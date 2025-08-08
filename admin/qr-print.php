<?php
/**
 * QR Code Print Page
 * Printable QR codes for menu access
 */

require_once '../classes/Auth.php';

// Require authentication
Auth::requireAuth();

// Get current user
$user = Auth::getUser();

// Refresh session
Auth::refreshSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu QR Codes - Print View | Plate Sushi St. Pete</title>
    <style>
        @page {
            size: 8.5in 11in;
            margin: 0.25in;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            text-align: center;
            max-width: 700px;
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-sizing: border-box;
        }
        
        .header {
            margin-bottom: 25px;
        }
        
        .restaurant-name {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .tagline {
            font-size: 1.2rem;
            color: #cccccc;
            margin-bottom: 30px;
        }
        
        .qr-section {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 40px;
            margin: 40px 0;
        }
        
        .qr-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            min-width: 200px;
        }
        
        .qr-code {
            width: 180px;
            height: 180px;
            background: white;
            border: 4px solid #ff6b35;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-label {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2d2d2d;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        
        .qr-description {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            line-height: 1.4;
        }
        
        .url {
            font-size: 1rem;
            color: #ff6b35;
            font-weight: bold;
            margin-top: 20px;
            word-break: break-all;
        }
        
        .footer {
            margin-top: 40px;
            font-size: 0.9rem;
            color: #999;
        }
        
        /* QR Code styling */
        .qr-canvas {
            border-radius: 6px;
        }
        
        /* Admin controls - hidden when printing */
        .admin-controls {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            gap: 10px;
        }
        
        .admin-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .admin-btn:hover {
            background: #5a6fd8;
        }
        
        .admin-btn.print-btn {
            background: #28a745;
        }
        
        .admin-btn.print-btn:hover {
            background: #218838;
        }
        
        @media print {
            .admin-controls {
                display: none !important;
            }
            
            body {
                background: white;
                color: black;
            }
            
            .container {
                background: white;
                border: 2px solid #ff6b35;
            }
            
            .restaurant-name {
                color: #ff6b35;
            }
            
            .tagline {
                color: #333;
            }
            
            .url {
                color: #ff6b35;
            }
            
            .footer {
                color: #666;
            }
        }
        
        /* Responsive design for smaller prints */
        @media (max-width: 600px) {
            .qr-section {
                flex-direction: column;
            }
            
            .restaurant-name {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-controls">
        <a href="./" class="admin-btn">← Back to Dashboard</a>
        <button onclick="window.print()" class="admin-btn print-btn">🖨️ Print</button>
        <button onclick="generatePDF()" class="admin-btn">📄 Generate PDF</button>
    </div>

    <div class="container">
        <div class="header">
            <img src="../vegas-style/images/food/Plate-Sushi-Logo.png" alt="Plate Sushi St. Pete Logo" style="max-height: 100px; margin-bottom: 20px;">
            <div class="tagline">Authentic Sushi & Fusion Tapas Experience</div>
        </div>
        
        <div class="qr-section">
            <div class="qr-item">
                <div class="qr-label">FOOD</div>
                <div class="qr-code" id="food-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=172x172&data=https://platestpete.com/menu" 
                         alt="Food Menu QR Code" 
                         style="width: 172px; height: 172px; border-radius: 6px;">
                </div>
                <div class="qr-description">
                    Scan to explore our sushi rolls, tapas, entrees, and warm bowls
                </div>
            </div>
            
            <div class="qr-item">
                <div class="qr-label">DRINKS</div>
                <div class="qr-code" id="drinks-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=172x172&data=https://platestpete.com/?menu=drinks" 
                         alt="Drinks Menu QR Code" 
                         style="width: 172px; height: 172px; border-radius: 6px;">
                </div>
                <div class="qr-description">
                    Discover our craft cocktails, sake selection, and beverage pairings
                </div>
            </div>
        </div>
        
        <div class="url">platestpete.com/menu</div>
        
        <div class="footer">
            St. Petersburg, Florida | Executive Chef Sean Thongsiri
        </div>
    </div>

    <script>
        function generatePDF() {
            // Use browser's print to PDF functionality
            const printSettings = {
                printBackground: true,
                marginsType: 1, // No margins
                pageSize: 'Letter'
            };
            
            // Trigger print dialog with PDF option
            window.print();
            
            // Alternative: You could implement a server-side PDF generation endpoint
            // window.location.href = 'generate-pdf.php';
        }

        // Auto-focus for print when loaded with print parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            setTimeout(() => {
                window.print();
            }, 1000);
        }
    </script>
</body>
</html>
