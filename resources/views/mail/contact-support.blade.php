<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Request</title>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
        }
        .header {
            margin-bottom: 32px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 16px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .info-group {
            margin-bottom: 24px;
        }
        .label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 4px;
        }
        .value {
            font-size: 16px;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
        }
        .message-content {
            font-size: 16px;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            white-space: pre-wrap;
            border-left: 4px solid #6366f1;
        }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Support Request</h1>
        </div>
        
        <div class="info-group">
            <div class="label">From</div>
            <div class="value">{{ $data['name'] }}</div>
        </div>

        <div class="info-group">
            <div class="label">Email</div>
            <div class="value">{{ $data['email'] }}</div>
        </div>

        <div class="info-group">
            <div class="label">Subject</div>
            <div class="value">{{ $data['subject'] }}</div>
        </div>

        <div class="info-group">
            <div class="label">Message</div>
            <div class="message-content">{{ $data['message'] }}</div>
        </div>

        <div class="footer">
            <p>Sent from the Support Portal</p>
        </div>
    </div>
</body>
</html>
