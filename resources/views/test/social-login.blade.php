<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>社群登入測試</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.2/axios.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Star Cloud 社群登入實測</h1>

        @if(isset($line_data))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded text-sm break-all">
            <h3 class="font-bold text-green-700 mb-2">Line Callback Data</h3>
            <pre>{{ json_encode($line_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            <p class="mt-2 text-gray-600">請將上方 code 透過後端 API 交換 access_token，再呼叫 /social-login API。</p>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Google Section -->
            <div class="bg-gray-50 p-4 rounded border">
                <h2 class="font-semibold text-lg mb-4 text-blue-600">Google Login</h2>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                    <input type="text" id="google-client-id" class="w-full p-2 border rounded text-sm" placeholder="YOUR_GOOGLE_CLIENT_ID">
                </div>

                <div id="g_id_onload"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleGoogleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left">
                </div>
                
                <button onclick="initGoogle()" class="mt-4 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 text-sm">
                    初始化 Google 按鈕
                </button>
            </div>

            <!-- Line Section -->
            <div class="bg-gray-50 p-4 rounded border">
                <h2 class="font-semibold text-lg mb-4 text-green-600">Line Login</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Channel ID</label>
                        <input type="text" id="line-channel-id" class="w-full p-2 border rounded text-sm" placeholder="1234567890">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Redirect URI</label>
                        <input type="text" id="line-redirect-uri" class="w-full p-2 border rounded text-sm" value="{{ url('/test/line/callback') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">State (Random)</label>
                        <input type="text" id="line-state" class="w-full p-2 border rounded text-sm" value="{{ Str::random(10) }}" readonly>
                    </div>
                </div>

                <button onclick="startLineLogin()" class="mt-6 w-full bg-green-500 text-white py-2 rounded hover:bg-green-600 font-bold">
                    Log in with Line
                </button>
            </div>
        </div>

        <!-- API Result -->
        <div class="mt-8 border-t pt-6">
            <h2 class="font-semibold text-lg mb-4 text-gray-800">API 執行結果 (/api/members/social-login)</h2>
            <div id="api-result" class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
                Waiting for action...
            </div>
        </div>
    </div>

    <script>
        // Google Initialization
        function initGoogle() {
            const clientId = document.getElementById('google-client-id').value;
            if (!clientId) {
                alert('請輸入 Google Client ID');
                return;
            }

            const wrapper = document.getElementById('g_id_onload');
            wrapper.setAttribute('data-client_id', clientId);
            
            // Re-render button if SDK already loaded
            if (window.google) {
                google.accounts.id.initialize({
                    client_id: clientId,
                    callback: handleGoogleCredentialResponse
                });
                google.accounts.id.renderButton(
                    document.querySelector(".g_id_signin"),
                    { theme: "outline", size: "large" }
                );
            }
        }

        function handleGoogleCredentialResponse(response) {
            console.log("Google JWT ID Token: " + response.credential);
            logResult("收到 Google ID Token...");
            
            // 解析 JWT (簡單解碼，正式環境應由後端驗證)
            const payload = parseJwt(response.credential);
            logResult("解析 JWT:\n" + JSON.stringify(payload, null, 2));

            // 呼叫後端 API
            callSocialLoginApi({
                provider: 'google',
                provider_id: payload.sub,
                email: payload.email,
                name: payload.name,
                avatar: payload.picture,
                access_token: response.credential // 這裡暫傳 id_token
            });
        }

        // Line Login Logic
        function startLineLogin() {
            const channelId = document.getElementById('line-channel-id').value;
            const redirectUri = document.getElementById('line-redirect-uri').value;
            const state = document.getElementById('line-state').value;

            if (!channelId) {
                alert('請輸入 Line Channel ID');
                return;
            }

            const url = `https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=${channelId}&redirect_uri=${encodeURIComponent(redirectUri)}&state=${state}&scope=profile%20openid%20email`;
            
            window.location.href = url;
        }

        // API Call
        async function callSocialLoginApi(data) {
            logResult("呼叫 API: /api/members/social-login...");
            try {
                const response = await axios.post('/api/members/social-login', data);
                logResult("API 回傳成功:\n" + JSON.stringify(response.data, null, 2));
            } catch (error) {
                logResult("API 錯誤:\n" + JSON.stringify(error.response ? error.response.data : error.message, null, 2));
            }
        }

        // Utilities
        function parseJwt (token) {
            var base64Url = token.split('.')[1];
            var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        }

        function logResult(msg) {
            const el = document.getElementById('api-result');
            el.innerText = msg + "\n\n" + el.innerText;
        }
    </script>
</body>
</html>
