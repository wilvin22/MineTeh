<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../database/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$delivery_id = (int)$_GET['id'];

// Get rider info
$rider = $supabase->select('riders', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$rider) {
    die('Access denied');
}

// Get delivery details
$delivery = $supabase->select('deliveries', '*', ['delivery_id' => $delivery_id], true);
if (!$delivery || $delivery['rider_id'] != $rider['rider_id']) {
    die('Delivery not found or access denied');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proof of Delivery - MineTeh</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .delivery-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .photo-upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .photo-upload-area:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .photo-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            margin-top: 15px;
            display: none;
        }

        .signature-pad {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            background: white;
            cursor: crosshair;
            touch-action: none;
        }

        .signature-controls {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-block {
            width: 100%;
            margin-top: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .signature-pad {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>📸 Proof of Delivery</h1>
            <p>Delivery #<?php echo $delivery_id; ?></p>
        </div>

        <div class="delivery-info">
            <div class="info-row">
                <span class="info-label">Delivery Address:</span>
                <span class="info-value"><?php echo htmlspecialchars($delivery['delivery_address']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Recipient:</span>
                <span class="info-value"><?php echo htmlspecialchars($delivery['recipient_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?php echo htmlspecialchars($delivery['recipient_phone'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <form id="proof-form" enctype="multipart/form-data">
            <input type="hidden" name="delivery_id" value="<?php echo $delivery_id; ?>">

            <!-- Photo Upload -->
            <div class="form-section">
                <h3>📷 Delivery Photo</h3>
                <div class="form-group">
                    <label>Take or upload a photo of the delivered item</label>
                    <div class="photo-upload-area" onclick="document.getElementById('photo-input').click()">
                        <div class="upload-icon">📸</div>
                        <p>Click to take photo or upload</p>
                        <small>Recommended: Photo of item at delivery location</small>
                    </div>
                    <input type="file" id="photo-input" name="delivery_photo" accept="image/*" capture="environment" style="display: none;">
                    <img id="photo-preview" class="photo-preview" alt="Preview">
                </div>
            </div>

            <!-- Signature -->
            <div class="form-section">
                <h3>✍️ Recipient Signature</h3>
                <div class="form-group">
                    <label>Ask recipient to sign below</label>
                    <canvas id="signature-pad" class="signature-pad" width="740" height="200"></canvas>
                    <div class="signature-controls">
                        <button type="button" class="btn btn-secondary" onclick="clearSignature()">Clear Signature</button>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-section">
                <h3>📝 Delivery Notes</h3>
                <div class="form-group">
                    <label>Additional notes (optional)</label>
                    <textarea name="delivery_notes" placeholder="Any additional information about the delivery..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">✅ Complete Delivery</button>
        </form>
    </div>

    <script>
        // Photo preview
        const photoInput = document.getElementById('photo-input');
        const photoPreview = document.getElementById('photo-preview');

        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Signature pad
        const canvas = document.getElementById('signature-pad');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // Set canvas size based on container
        function resizeCanvas() {
            const container = canvas.parentElement;
            canvas.width = container.offsetWidth - 4;
            canvas.height = 200;
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;
            [lastX, lastY] = [x, y];
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();

            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.stroke();

            [lastX, lastY] = [x, y];
        }

        function stopDrawing() {
            isDrawing = false;
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        // Form submission
        document.getElementById('proof-form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate photo
            if (!photoInput.files || photoInput.files.length === 0) {
                alert('Please upload a delivery photo');
                return;
            }

            // Validate signature
            const signatureData = canvas.toDataURL();
            if (signatureData === canvas.toDataURL('image/png')) {
                // Check if canvas is blank
                const blankCanvas = document.createElement('canvas');
                blankCanvas.width = canvas.width;
                blankCanvas.height = canvas.height;
                if (signatureData === blankCanvas.toDataURL()) {
                    alert('Please get recipient signature');
                    return;
                }
            }

            const formData = new FormData(this);
            formData.append('signature', signatureData);

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';

            fetch('../actions/rider-complete-delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Delivery completed successfully!');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Error: ' + (data.message || 'Failed to complete delivery'));
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✅ Complete Delivery';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to complete delivery');
                submitBtn.disabled = false;
                submitBtn.textContent = '✅ Complete Delivery';
            });
        });
    </script>
</body>
</html>
