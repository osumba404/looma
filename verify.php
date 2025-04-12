<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Phone - Looma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center mb-4">Verify Your Phone</h2>
            <p class="text-center">Enter the 6-digit code sent to your phone.</p>
            <div id="verify-alert" class="alert"></div>
            <form id="verify-form" onsubmit="verifyPhone(event)">
                <input type="text" name="code" class="form-control" placeholder="Verification Code" required maxlength="6">
                <button type="submit" class="btn btn-primary">
                    Verify
                    <span class="spinner spinner-border spinner-border-sm"></span>
                </button>
            </form>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>