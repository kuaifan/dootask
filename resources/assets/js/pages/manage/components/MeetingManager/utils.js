const errorMessages = {
    "UNEXPECTED_ERROR": {
        "zh": "无法处理的、非预期的错误，通常这个错误会有具体的错误提示。",
        "zh-CHT": "無法處理的、非預期的錯誤，通常這個錯誤會有具體的錯誤提示。",
        "en": "An unhandled, unexpected error. This error usually comes with a specific error message.",
        "ko": "처리되지 않은 예기치 않은 오류입니다. 일반적으로 이 오류에는 구체적인 오류 메시지가 포함됩니다.",
        "ja": "処理できない予期しないエラーです。通常、このエラーには具体的なエラーメッセージが含まれています。",
        "de": "Ein unbehandelter, unerwarteter Fehler. Dieser Fehler enthält normalerweise eine spezifische Fehlermeldung.",
        "fr": "Une erreur inattendue et non gérée. Cette erreur est généralement accompagnée d'un message d'erreur spécifique.",
        "id": "Kesalahan tak terduga dan tidak tertangani. Kesalahan ini biasanya disertai dengan pesan kesalahan spesifik.",
        "ru": "Необработанная, неожиданная ошибка. Обычно эта ошибка сопровождается конкретным сообщением об ошибке."
    },
    "UNEXPECTED_RESPONSE": {
        "zh": "服务端返回了非预期的响应。这个错误通常是在网络问题导致客户端与服务端状态不一致时抛出。",
        "zh-CHT": "服務端返回了非預期的響應。這個錯誤通常是在網絡問題導致客戶端與服務端狀態不一致時拋出。",
        "en": "The server returned an unexpected response. This error usually occurs when network issues cause inconsistency between client and server states.",
        "ko": "서버가 예기치 않은 응답을 반환했습니다. 이 오류는 일반적으로 네트워크 문제로 인해 클라이언트와 서버 상태가 불일치할 때 발생합니다.",
        "ja": "サーバーから予期しない応答が返されました。このエラーは通常、ネットワークの問題によってクライアントとサーバーの状態が一致しない場合に発生します。",
        "de": "Der Server hat eine unerwartete Antwort zurückgegeben. Dieser Fehler tritt normalerweise auf, wenn Netzwerkprobleme zu Inkonsistenzen zwischen Client- und Serverzuständen führen.",
        "fr": "Le serveur a renvoyé une réponse inattendue. Cette erreur survient généralement lorsque des problèmes de réseau causent une incohérence entre les états du client et du serveur.",
        "id": "Server mengembalikan respons yang tidak terduga. Kesalahan ini biasanya terjadi ketika masalah jaringan menyebabkan ketidaksesuaian antara status klien dan server.",
        "ru": "Сервер вернул неожиданный ответ. Эта ошибка обычно возникает, когда проблемы с сетью вызывают несоответствие между состояниями клиента и сервера."
    },
    "INVALID_PARAMS": {
        "zh": "非法参数。",
        "zh-CHT": "非法參數。",
        "en": "Invalid parameters.",
        "ko": "잘못된 매개변수입니다.",
        "ja": "無効なパラメータです。",
        "de": "Ungültige Parameter.",
        "fr": "Paramètres invalides.",
        "id": "Parameter tidak valid.",
        "ru": "Недопустимые параметры."
    },
    "NOT_SUPPORTED": {
        "zh": "浏览器不支持。",
        "zh-CHT": "瀏覽器不支持。",
        "en": "Browser not supported.",
        "ko": "브라우저가 지원되지 않습니다.",
        "ja": "ブラウザはサポートされていません。",
        "de": "Der Browser wird nicht unterstützt.",
        "fr": "Le navigateur n'est pas pris en charge.",
        "id": "Browser tidak didukung.",
        "ru": "Браузер не поддерживается."
    },
    "INVALID_OPERATION": {
        "zh": "非法操作，通常是因为在当前状态不能进行该操作。",
        "zh-CHT": "非法操作，通常是因為在當前狀態不能進行該操作。",
        "en": "Invalid operation, usually because the operation cannot be performed in the current state.",
        "ko": "잘못된 작업입니다. 일반적으로 현재 상태에서 해당 작업을 수행할 수 없기 때문입니다.",
        "ja": "無効な操作です。通常は、現在の状態で操作を実行できないためです。",
        "de": "Ungültiger Vorgang, normalerweise weil der Vorgang im aktuellen Zustand nicht durchgeführt werden kann.",
        "fr": "Opération invalide, généralement parce que l'opération ne peut pas être effectuée dans l'état actuel.",
        "id": "Operasi tidak valid. Biasanya karena operasi tidak dapat dilakukan dalam status saat ini.",
        "ru": "Недопустимая операция, обычно потому, что операция не может быть выполнена в текущем состоянии."
    },
    "OPERATION_ABORTED": {
        "zh": "操作中止，通常是因为网络质量差或连接断开导致与语音服务器通信失败。",
        "zh-CHT": "操作中止，通常是因為網絡質量差或連接斷開導致與語音服務器通信失敗。",
        "en": "Operation aborted, usually due to communication failure with the voice server caused by poor network quality or disconnection.",
        "ko": "작업이 중단되었습니다. 일반적으로 네트워크 품질이 나쁘거나 연결이 끊어져 음성 서버와 통신에 실패했기 때문입니다.",
        "ja": "操作が中止されました。通常は、ネットワーク品質が悪いか、接続が切断されたため、音声サーバーとの通信に失敗したためです。",
        "de": "Vorgang abgebrochen, normalerweise aufgrund von Kommunikationsfehlern mit dem Sprachserver aufgrund von schlechter Netzwerkqualität oder Verbindungsunterbrechung.",
        "fr": "Opération annulée, généralement en raison d'une défaillance de communication avec le serveur vocal due à une mauvaise qualité du réseau ou à une déconnexion.",
        "id": "Operasi dibatalkan. Biasanya karena komunikasi dengan server suara gagal karena kualitas jaringan yang buruk atau koneksi yang terputus.",
        "ru": "Операция отменена, обычно из-за сбоя связи с сервером голоса из-за плохого качества сети или разрыва соединения."
    },
    "WEB_SECURITY_RESTRICT": {
        "zh": "浏览器安全策略限制。",
        "zh-CHT": "瀏覽器安全策略限制。",
        "en": "Browser security policy restriction.",
        "ko": "브라우저 보안 정책 제한입니다.",
        "ja": "ブラウザのセキュリティポリシー制限です。",
        "de": "Einschränkung der Browser-Sicherheitsrichtlinie.",
        "fr": "Restriction de la politique de sécurité du navigateur.",
        "id": "Pembatasan kebijakan keamanan browser.",
        "ru": "Ограничение политики безопасности браузера."
    },
    "NO_ACTIVE_STATUS": {
        "zh": "语音项目未激活或被禁用。",
        "zh-CHT": "語音項目未激活或被禁用。",
        "en": "Voice project is not activated or has been disabled.",
        "ko": "음성 프로젝트가 활성화되지 않았거나 비활성화되었습니다.",
        "ja": "音声プロジェクトがアクティブ化されていないか、無効になっています。",
        "de": "Sprachprojekt ist nicht aktiviert oder wurde deaktiviert.",
        "fr": "Le projet vocal n'est pas activé ou a été désactivé.",
        "id": "Proyek suara tidak diaktifkan atau dinonaktifkan.",
        "ru": "Проект голоса не активирован или отключен."
    },
    "NETWORK_TIMEOUT": {
        "zh": "请求超时，通常是因为网络质量差或连接断开导致与语音服务器通信失败。",
        "zh-CHT": "請求超時，通常是因為網絡質量差或連接斷開導致與語音服務器通信失敗。",
        "en": "Request timeout, usually due to communication failure with the voice server caused by poor network quality or disconnection.",
        "ko": "요청이 시간 초과되었습니다. 일반적으로 네트워크 품질이 나쁘거나 연결이 끊어져 음성 서버와 통신에 실패했기 때문입니다.",
        "ja": "リクエストがタイムアウトしました。通常は、ネットワーク品質が悪いか、接続が切断されたため、音声サーバーとの通信に失敗したためです。",
        "de": "Anforderungstimeout, normalerweise aufgrund von Kommunikationsfehlern mit dem Sprachserver aufgrund von schlechter Netzwerkqualität oder Verbindungsunterbrechung.",
        "fr": "Délai d'attente de la requête dépassé, généralement en raison d'une défaillance de communication avec le serveur vocal due à une mauvaise qualité du réseau ou à une déconnexion.",
        "id": "Waktu permintaan habis. Biasanya karena komunikasi dengan server suara gagal karena kualitas jaringan yang buruk atau koneksi yang terputus.",
        "ru": "Время запроса истекло. Обычно из-за сбоя связи с сервером голоса из-за плохого качества сети или разрыва соединения."
    },
    "NETWORK_RESPONSE_ERROR": {
        "zh": "响应错误，一般是状态码非法。",
        "zh-CHT": "響應錯誤，一般是狀態碼非法。",
        "en": "Response error, usually due to invalid status code.",
        "ko": "응답 오류입니다. 일반적으로 상태 코드가 잘못되기 때문입니다.",
        "ja": "レスポンスエラーです。通常は、ステータスコードが無効であるためです。",
        "de": "Antwortfehler, normalerweise aufgrund eines ungültigen Statuscodes.",
        "fr": "Erreur de réponse, généralement en raison d'un code de statut invalide.",
        "id": "Kesalahan respons. Biasanya karena kode status tidak valid.",
        "ru": "Ошибка ответа, обычно из-за недопустимого кода состояния."
    },
    "NETWORK_ERROR": {
        "zh": "无法定位的网络错误。",
        "zh-CHT": "無法定位的網絡錯誤。",
        "en": "Unlocatable network error.",
        "ko": "위치할 수 없는 네트워크 오류입니다.",
        "ja": "特定できないネットワークエラーです。",
        "de": "Nicht lokalisierbarer Netzwerkfehler.",
        "fr": "Erreur réseau non localisable.",
        "id": "Kesalahan jaringan yang tidak dapat ditemukan.",
        "ru": "Нелокализуемая ошибка сети."
    },
    "WS_ABORT": {
        "zh": "请求语音服务器过程中 WebSocket 断开。",
        "zh-CHT": "請求語音服務器過程中 WebSocket 斷開。",
        "en": "WebSocket disconnected during voice server request.",
        "ko": "음성 서버 요청 중 WebSocket 연결이 끊어졌습니다.",
        "ja": "音声サーバーのリクエスト中にWebSocketが切断されました。",
        "de": "WebSocket wurde während der Anfrage an den Sprachserver getrennt.",
        "fr": "La connexion WebSocket a été interrompue pendant la requête au serveur vocal.",
        "id": "Koneksi WebSocket terputus selama permintaan server suara.",
        "ru": "Соединение WebSocket было прервано во время запроса к серверу голоса."
    },
    "WS_DISCONNECT": {
        "zh": "请求语音服务器前，WebSocket 就已经断开。",
        "zh-CHT": "請求語音服務器前，WebSocket 就已經斷開。",
        "en": "WebSocket was already disconnected before requesting the voice server.",
        "ko": "음성 서버를 요청하기 전에 WebSocket 연결이 이미 끊어졌습니다.",
        "ja": "音声サーバーをリクエストする前に、WebSocketがすでに切断されていました。",
        "de": "WebSocket wurde bereits vor der Anfrage an den Sprachserver getrennt.",
        "fr": "La connexion WebSocket était déjà interrompue avant de demander le serveur vocal.",
        "id": "Koneksi WebSocket sudah terputus sebelum meminta server suara.",
        "ru": "Соединение WebSocket уже было разорвано до запроса сервера голоса."
    },
    "WS_ERR": {
        "zh": "WebSocket 连接发生错误。",
        "zh-CHT": "WebSocket 連接發生錯誤。",
        "en": "WebSocket connection error occurred.",
        "ko": "WebSocket 연결 오류가 발생했습니다.",
        "ja": "WebSocket接続エラーが発生しました。",
        "de": "WebSocket-Verbindungsfehler ist aufgetreten.",
        "fr": "Une erreur de connexion WebSocket s'est produite.",
        "id": "Kesalahan koneksi WebSocket terjadi.",
        "ru": "Произошла ошибка соединения WebSocket."
    },
    "ENUMERATE_DEVICES_FAILED": {
        "zh": "枚举本地设备失败，一般是由于浏览器限制。",
        "zh-CHT": "枚舉本地設備失敗，一般是由於瀏覽器限制。",
        "en": "Failed to enumerate local devices, usually due to browser restrictions.",
        "ko": "로컬 장치를 열거하지 못했습니다. 일반적으로 브라우저 제한으로 인해 발생합니다.",
        "ja": "ローカルデバイスの列挙に失敗しました。通常は、ブラウザの制限によるものです。",
        "de": "Auflistung lokaler Geräte fehlgeschlagen, normalerweise aufgrund von Browser-Einschränkungen.",
        "fr": "Échec de l'énumération des périphériques locaux, généralement en raison de restrictions de navigateur.",
        "id": "Gagal menghitung perangkat lokal. Biasanya karena pembatasan browser.",
        "ru": "Не удалось перечислить локальные устройства, обычно из-за ограничений браузера."
    },
    "DEVICE_NOT_FOUND": {
        "zh": "无法找到指定设备。",
        "zh-CHT": "無法找到指定設備。",
        "en": "Specified device not found.",
        "ko": "지정된 장치를 찾을 수 없습니다.",
        "ja": "指定されたデバイスが見つかりませんでした。",
        "de": "Das angegebene Gerät wurde nicht gefunden.",
        "fr": "Le périphérique spécifié n'a pas été trouvé.",
        "id": "Perangkat yang ditentukan tidak ditemukan.",
        "ru": "Устройство не найдено."
    },
    "TRACK_IS_DISABLED": {
        "zh": "轨道被禁用，通常因为轨道设置了 Track.setEnabled(false)。",
        "zh-CHT": "軌道被禁用，通常是因為軌道設定了 Track.setEnabled(false)。",
        "en": "Track is disabled, usually because Track.setEnabled(false) was set.",
        "ko": "트랙이 비활성화되었습니다. 일반적으로 트랙에 Track.setEnabled(false)가 설정되었기 때문입니다.",
        "ja": "トラックが無効になっています。通常は、トラックにTrack.setEnabled(false)が設定されているためです。",
        "de": "Die Spur ist deaktiviert, normalerweise weil Track.setEnabled(false) gesetzt wurde.",
        "fr": "La piste est désactivée, généralement parce que Track.setEnabled(false) a été défini.",
        "id": "Trek dinonaktifkan. Biasanya karena trek memiliki Track.setEnabled(false).",
        "ru": "Трек отключен, обычно потому, что Track.setEnabled(false) был установлен."
    },
    "SHARE_AUDIO_NOT_ALLOWED": {
        "zh": "屏幕共享音频时终端用户没有点击分享音频。",
        "zh-CHT": "螢幕共享音頻時終端用戶沒有點擊分享音頻。",
        "en": "End user did not click to share audio during screen sharing.",
        "ko": "화면 공유 중에 사용자가 오디오 공유를 클릭하지 않았습니다.",
        "ja": "スクリーンシェアリング中に、エンドユーザーがオーディオを共有することをクリックしなかったためです。",
        "de": "Der Endbenutzer hat während der Bildschirmfreigabe nicht auf Audio-Freigabe geklickt.",
        "fr": "L'utilisateur final n'a pas cliqué sur le partage audio pendant le partage d'écran.",
        "id": "Pengguna akhir tidak mengklik bagikan audio selama berbagi layar.",
        "ru": "Конечный пользователь не нажал на кнопку «Поделиться аудио» во время обмена экраном."
    },
    "CHROME_PLUGIN_NO_RESPONSE": {
        "zh": "Chrome 屏幕共享插件无响应。",
        "zh-CHT": "Chrome 螢幕共享插件無響應。",
        "en": "Chrome screen sharing plugin not responding.",
        "ko": "크롬 화면 공유 플러그인이 응답하지 않습니다.",
        "ja": "Chromeのスクリーンシェアリングプラグインが応答していません。",
        "de": "Chrome-Bildschirmfreigabe-Plug-in reagiert nicht.",
        "fr": "Le plug-in de partage d'écran Chrome ne répond pas.",
        "id": "Plugin berbagi layar Chrome tidak merespons.",
        "ru": "Плагин для обмена экраном Chrome не отвечает."
    },
    "CHROME_PLUGIN_NOT_INSTALL": {
        "zh": "Chrome 屏幕共享插件没有安装。",
        "zh-CHT": "Chrome 螢幕共享插件沒有安裝。",
        "en": "Chrome screen sharing plugin not installed.",
        "ko": "크롬 화면 공유 플러그인이 설치되지 않았습니다.",
        "ja": "Chromeのスクリーンシェアリングプラグインがインストールされていません。",
        "de": "Chrome-Bildschirmfreigabe-Plug-in nicht installiert.",
        "fr": "Le plug-in de partage d'écran Chrome n'est pas installé.",
        "id": "Plugin berbagi layar Chrome tidak terinstal.",
        "ru": "Плагин для обмена экраном Chrome не установлен."
    },
    "MEDIA_OPTION_INVALID": {
        "zh": "不支持的媒体采集的参数。",
        "zh-CHT": "不支持的媒體採集的參數。",
        "en": "Unsupported media capture parameters.",
        "ko": "지원되지 않는 미디어 캡처 매개변수입니다.",
        "ja": "サポートされていないメディアキャプチャパラメータです。",
        "de": "Nicht unterstützte Medienaufnahmeparameter.",
        "fr": "Paramètres de capture de médias non pris en charge.",
        "id": "Parameter pengambilan media tidak didukung.",
        "ru": "Неподдерживаемые параметры захвата медиа."
    },
    "CONSTRAINT_NOT_SATISFIED": {
        "zh": "不支持的媒体采集的参数。",
        "zh-CHT": "不支持的媒體採集的參數。",
        "en": "Unsupported media capture parameters.",
        "ko": "지원되지 않는 미디어 캡처 매개변수입니다.",
        "ja": "サポートされていないメディアキャプチャパラメータです。",
        "de": "Nicht unterstützte Medienaufnahmeparameter.",
        "fr": "Paramètres de capture de médias non pris en charge.",
        "id": "Parameter pengambilan media tidak didukung.",
        "ru": "Неподдерживаемые параметры захвата медиа."
    },
    "PERMISSION_DENIED": {
        "zh": "获取媒体设备权限被拒绝。",
        "zh-CHT": "获取媒體設備權限被拒絕。",
        "en": "Permission to access media devices was denied.",
        "ko": "미디어 장치에 대한 권한이 거부되었습니다.",
        "ja": "メディアデバイスへのアクセス権限が拒否されました。",
        "de": "Die Erlaubnis zum Zugriff auf Medien-Geräte wurde verweigert.",
        "fr": "L'autorisation d'accéder aux périphériques multimédias a été refusée.",
        "id": "Izin mengakses perangkat media ditolak.",
        "ru": "Доступ к устройствам мультимедиа был запрещен."
    },
    "NOT_READABLE": {
        "zh": "用户已经授权，但媒体设备无法访问。",
        "zh-CHT": "用戶已經授權，但媒體設備無法存取。",
        "en": "User has authorized, but media device cannot be accessed.",
        "ko": "사용자가 이미 권한을 부여했지만 미디어 장치에 액세스할 수 없습니다.",
        "ja": "ユーザーはすでに権限を付与していますが、メディアデバイスにアクセスできません。",
        "de": "Der Benutzer hat bereits die Erlaubnis erteilt, aber das Medien-Gerät kann nicht zugänglich gemacht werden.",
        "fr": "L'utilisateur a déjà autorisé l'accès, mais le périphérique multimédia ne peut pas être accédé.",
        "id": "Pengguna telah memberikan izin, tetapi perangkat media tidak dapat diakses.",
        "ru": "Пользователь уже предоставил разрешение, но устройство мультимедиа не может быть доступно."
    },
    "FETCH_AUDIO_FILE_FAILED": {
        "zh": "下载在线音频文件失败。",
        "zh-CHT": "下載在線音頻文件失敗。",
        "en": "Failed to download online audio file.",
        "ko": "온라인 오디오 파일 다운로드에 실패했습니다.",
        "ja": "オンラインオーディオファイルのダウンロードに失敗しました。",
        "de": "Herunterladen der Online-Audio-Datei fehlgeschlagen.",
        "fr": "Échec du téléchargement du fichier audio en ligne.",
        "id": "Gagal mengunduh file audio online.",
        "ru": "Не удалось скачать файл аудио онлайн."
    },
    "READ_LOCAL_AUDIO_FILE_ERROR": {
        "zh": "读取本地音频文件失败。",
        "zh-CHT": "讀取本地音頻文件失敗。",
        "en": "Failed to read local audio file.",
        "ko": "로컬 오디오 파일 읽기에 실패했습니다.",
        "ja": "ローカルオーディオファイルの読み取りに失敗しました。",
        "de": "Lokale Audio-Datei konnte nicht gelesen werden.",
        "fr": "Échec de la lecture du fichier audio local.",
        "id": "Gagal membaca file audio lokal.",
        "ru": "Не удалось прочитать локальный файл аудио."
    },
    "DECODE_AUDIO_FILE_FAILED": {
        "zh": "音频文件解码失败，可能是因为音频文件的编码格式是浏览器 WebAudio 不支持的编码格式。",
        "zh-CHT": "音頻文件解碼失敗，可能是因為音頻文件的編碼格式是瀏覽器 WebAudio 不支持的編碼格式。",
        "en": "Failed to decode audio file, possibly because the audio file encoding format is not supported by browser WebAudio.",
        "ko": "오디오 파일 디코딩에 실패했습니다. 브라우저 WebAudio에서 지원하지 않는 오디오 파일 인코딩 형식 때문일 수 있습니다.",
        "ja": "オーディオファイルのデコードに失敗しました。ブラウザのWebAudioがサポートしていないオーディオファイルのエンコード形式である可能性があります。",
        "de": "Audio-Datei konnte nicht decodiert werden, möglicherweise weil das Audio-Datei-Codec-Format vom Browser-WebAudio nicht unterstützt wird.",
        "fr": "Échec de la décodage du fichier audio, probablement parce que le format de codage du fichier audio n'est pas pris en charge par le navigateur WebAudio.",
        "id": "Gagal mendekode file audio. Mungkin karena format enkode file audio tidak didukung oleh WebAudio browser.",
        "ru": "Не удалось декодировать файл аудио, возможно, потому что формат кодирования файла аудио не поддерживается браузером WebAudio."
    },
    "UID_CONFLICT": {
        "zh": "同一个频道内 UID 重复。",
        "zh-CHT": "同一個頻道內 UID 重複。",
        "en": "Duplicate UID within the same channel.",
        "ko": "같은 채널 내에서 UID가 중복됩니다.",
        "ja": "同じチャンネル内でUIDが重複しています。",
        "de": "Doppelte UID innerhalb des gleichen Kanals.",
        "fr": "UID en double dans le même canal.",
        "id": "UID ganda dalam saluran yang sama.",
        "ru": "Дублирование UID в одном и том же канале."
    },
    "INVALID_UINT_UID_FROM_STRING_UID": {
        "zh": "String UID 分配服务返回了非法的 int UID。",
        "zh-CHT": "String UID 分配服務返回了非法的 int UID。",
        "en": "String UID allocation service returned an invalid int UID.",
        "ko": "문자열 UID 할당 서비스가 잘못된 정수 UID를 반환했습니다.",
        "ja": "String UID割り当てサービスが無効なint UIDを返しました。",
        "de": "String-UID-Zuweisungsdienst hat eine ungültige int-UID zurückgegeben.",
        "fr": "Le service d'allocation d'UID de chaîne a retourné un UID entier non valide.",
        "id": "Layanan alokasi UID string mengembalikan UID int yang tidak valid.",
        "ru": "Служба распределения UID строки вернула недопустимый целочисленный UID."
    },
    "CAN_NOT_GET_PROXY_SERVER": {
        "zh": "无法获取云代理服务地址。",
        "zh-CHT": "無法获取雲代理服務地址。",
        "en": "Unable to get cloud proxy server address.",
        "ko": "클라우드 프록시 서버 주소를 가져올 수 없습니다.",
        "ja": "クラウドプロキシサーバーのアドレスを取得できません。",
        "de": "Cloud-Proxy-Server-Adresse kann nicht abgerufen werden.",
        "fr": "Impossible d'obtenir l'adresse du serveur proxy cloud.",
        "id": "Tidak dapat mendapatkan alamat server proxy cloud.",
        "ru": "Не удалось получить адрес сервера прокси облачных услуг."
    },
    "CAN_NOT_GET_GATEWAY_SERVER": {
        "zh": "无法获取语音服务器地址。",
        "zh-CHT": "無法获取語音服務器地址。",
        "en": "Unable to get voice server address.",
        "ko": "음성 서버 주소를 가져올 수 없습니다.",
        "ja": "音声サーバーのアドレスを取得できません。",
        "de": "Sprachserver-Adresse kann nicht abgerufen werden.",
        "fr": "Impossible d'obtenir l'adresse du serveur vocal.",
        "id": "Tidak dapat mendapatkan alamat server suara.",
        "ru": "Не удалось получить адрес сервера голоса."
    },
    "INVALID_LOCAL_TRACK": {
        "zh": "传入了非法的 LocalTrack。",
        "zh-CHT": "傳入了非法的 LocalTrack。",
        "en": "Invalid LocalTrack passed.",
        "ko": "잘못된 로컬 트랙이 전달되었습니다.",
        "ja": "無効なLocalTrackが渡されました。",
        "de": "Ungültiger LocalTrack übergeben.",
        "fr": "Piste locale invalide transmise.",
        "id": "Trek lokal tidak valid dilewatkan.",
        "ru": "Передан недопустимый LocalTrack."
    },
    "CAN_NOT_PUBLISH_MULTIPLE_VIDEO_TRACKS": {
        "zh": "一个 Client 发布多个视频轨道。",
        "zh-CHT": "一個 Client 發布多個視頻軌道。",
        "en": "A Client publishing multiple video tracks.",
        "ko": "클라이언트가 여러 비디오 트랙을 발행합니다.",
        "ja": "クライアントが複数のビデオトラックを公開しています。",
        "de": "Ein Client veröffentlicht mehrere Videospuren.",
        "fr": "Un client publie plusieurs pistes vidéo.",
        "id": "Klien menerbitkan beberapa trek video.",
        "ru": "Клиент публикует несколько видеодорожек."
    },
    "INVALID_REMOTE_USER": {
        "zh": "非法的远端用户，可能是远端用户不在频道内或还未发布任何媒体轨道。",
        "zh-CHT": "非法的遠端用戶，可能是遠端用戶不在頻道內或還未發布任何媒體軌道。",
        "en": "Invalid remote user, possibly because the remote user is not in the channel or has not published any media tracks.",
        "ko": "잘못된 원격 사용자입니다. 원격 사용자가 채널에 있지 않거나 미디어 트랙을 발행하지 않았을 수 있습니다.",
        "ja": "無効なリモートユーザーです。リモートユーザーがチャンネルにいないか、メディアトラックを公開していない可能性があります。",
        "de": "Ungültiger Remote-Benutzer, möglicherweise weil der Remote-Benutzer sich nicht im Kanal befindet oder noch keine Medien-Spuren veröffentlicht hat.",
        "fr": "Utilisateur distant invalide, probablement parce que l'utilisateur distant n'est pas dans le canal ou n'a pas publié de pistes multimédias.",
        "id": "Pengguna jarak jauh tidak valid. Mungkin karena pengguna jarak jauh tidak berada di saluran atau belum menerbitkan trek media apa pun.",
        "ru": "Недопустимый удаленный пользователь, возможно, потому что удаленный пользователь не находится в канале или еще не опубликовал ни одной дорожки мультимедиа."
    },
    "REMOTE_USER_IS_NOT_PUBLISHED": {
        "zh": "远端用户已发布了音频或视频轨道，但不是与你的订阅操作所指定的类型不符。",
        "zh-CHT": "遠端用戶已發布了音頻或視頻軌道，但不是與你的訂閱操作所指定的類型不符。",
        "en": "Remote user has published audio or video tracks, but not of the type specified by your subscription operation.",
        "ko": "원격 사용자가 오디오 또는 비디오 트랙을 발행했지만 구독 작업에서 지정한 유형과 일치하지 않습니다.",
        "ja": "リモートユーザーはオーディオまたはビデオトラックを公開していますが、サブスクリプション操作で指定されたタイプと一致しません。",
        "de": "Der Remote-Benutzer hat Audio- oder Videospuren veröffentlicht, aber nicht vom Typ, der durch Ihren Abonnementvorgang angegeben wurde.",
        "fr": "L'utilisateur distant a publié des pistes audio ou vidéo, mais pas du type spécifié par votre opération d'abonnement.",
        "id": "Pengguna jarak jauh telah menerbitkan trek audio atau video, tetapi tidak sesuai dengan jenis yang ditentukan oleh operasi berlangganan Anda.",
        "ru": "Удаленный пользователь опубликовал аудио- или видеодорожки, но не того типа, который указан в вашей операции подписки."
    },
    "ERR_TOO_MANY_BROADCASTERS": {
        "zh": "频道内主播人数超过上限。",
        "zh-CHT": "頻道內主播人數超過上限。",
        "en": "Number of broadcasters in the channel exceeds the limit.",
        "ko": "채널 내 방송자 수는 제한을 초과했습니다.",
        "ja": "チャンネル内のブロードキャスターの数が上限を超えています。",
        "de": "Die Anzahl der Broadcaster im Kanal überschreitet das Limit.",
        "fr": "Le nombre de diffuseurs dans le canal dépasse la limite.",
        "id": "Jumlah penyiar di saluran melebihi batas.",
        "ru": "Количество вещателей в канале превышает предел."
    },
    "ERR_TOO_MANY_SUBSCRIBERS": {
        "zh": "当前用户订阅的主播人数超过上限。",
        "zh-CHT": "當前用戶訂閱的主播人數超過上限。",
        "en": "Number of subscribers to the current user's channel exceeds the limit.",
        "ko": "현재 사용자의 채널 구독자 수는 제한을 초과했습니다.",
        "ja": "現在のユーザーのチャンネルのサブスクライバー数が上限を超えています。",
        "de": "Die Anzahl der Abonnenten des aktuellen Benutzers überschreitet das Limit.",
        "fr": "Le nombre d'abonnés du canal de l'utilisateur actuel dépasse la limite.",
        "id": "Jumlah pelanggan saluran pengguna saat ini melebihi batas.",
        "ru": "Количество подписчиков канала текущего пользователя превышает предел."
    },
    "LIVE_STREAMING_TASK_CONFLICT": {
        "zh": "推流任务已经存在。",
        "zh-CHT": "推流任務已經存在。",
        "en": "Live streaming task already exists.",
        "ko": "라이브 스트리밍 작업이 이미 존재합니다.",
        "ja": "ライブストリーミングタスクがすでに存在します。",
        "de": "Live-Streaming-Aufgabe existiert bereits.",
        "fr": "La tâche de diffusion en direct existe déjà.",
        "id": "Tugas streaming langsung sudah ada.",
        "ru": "Задача прямой трансляции уже существует."
    },
    "LIVE_STREAMING_INVALID_ARGUMENT": {
        "zh": "推流参数错误。",
        "zh-CHT": "推流參數錯誤。",
        "en": "Invalid live streaming argument.",
        "ko": "라이브 스트리밍 인수가 잘못되었습니다.",
        "ja": "ライブストリーミング引数が無効です。",
        "de": "Ungültiger Live-Streaming-Parameter.",
        "fr": "Argument de diffusion en direct invalide.",
        "id": "Argumen streaming langsung tidak valid.",
        "ru": "Недопустимый аргумент прямой трансляции."
    },
    "LIVE_STREAMING_INTERNAL_SERVER_ERROR": {
        "zh": "推流服务器内部错误。",
        "zh-CHT": "推流服務器内部錯誤。",
        "en": "Live streaming server internal error.",
        "ko": "라이브 스트리밍 서버 내부 오류입니다.",
        "ja": "ライブストリーミングサーバーの内部エラーです。",
        "de": "Interner Fehler des Live-Streaming-Servers.",
        "fr": "Erreur interne du serveur de diffusion en direct.",
        "id": "Kesalahan internal server streaming langsung.",
        "ru": "Внутренняя ошибка сервера прямой трансляции."
    },
    "LIVE_STREAMING_PUBLISH_STREAM_NOT_AUTHORIZED": {
        "zh": "推流 URL 被占用。",
        "zh-CHT": "推流 URL 被佔用。",
        "en": "Live streaming URL is occupied.",
        "ko": "라이브 스트리밍 URL이 이미 사용 중입니다.",
        "ja": "ライブストリーミングURLはすでに占有されています。",
        "de": "Live-Streaming-URL ist besetzt.",
        "fr": "L'URL de diffusion en direct est occupée.",
        "id": "URL streaming langsung sudah digunakan.",
        "ru": "URL прямой трансляции уже используется."
    },
    "LIVE_STREAMING_CDN_ERROR": {
        "zh": "推流的目标 CDN 出现错误导致推流失败。",
        "zh-CHT": "推流的目標 CDN 出現錯誤導致推流失敗。",
        "en": "Live streaming failed due to error in target CDN.",
        "ko": "대상 CDN에서 오류가 발생하여 라이브 스트리밍에 실패했습니다.",
        "ja": "ターゲットCDNのエラーにより、ライブストリーミングに失敗しました。",
        "de": "Live-Streaming fehlgeschlagen aufgrund eines Fehlers im Ziel-CDN.",
        "fr": "La diffusion en direct a échoué en raison d'une erreur dans le CDN cible.",
        "id": "Streaming langsung gagal karena kesalahan di CDN target.",
        "ru": "Прямая трансляция не удалась из-за ошибки в целевом CDN."
    },
    "LIVE_STREAMING_INVALID_RAW_STREAM": {
        "zh": "推流超时。",
        "zh-CHT": "推流超時。",
        "en": "Live streaming timed out.",
        "ko": "라이브 스트리밍이 시간 초과되었습니다.",
        "ja": "ライブストリーミングがタイムアウトしました。",
        "de": "Live-Streaming hat einen Timeout erreicht.",
        "fr": "La diffusion en direct a expiré.",
        "id": "Streaming langsung telah kedaluwarsa.",
        "ru": "Прямая трансляция timed out."
    },
    "CROSS_CHANNEL_WAIT_STATUS_ERROR": {
        "zh": "等待 RTCClient.on(channel-media-relay-state) 回调出错。",
        "zh-CHT": "等待 RTCClient.on(channel-media-relay-state) 回調出錯。",
        "en": "Error waiting for RTCClient.on(channel-media-relay-state) callback.",
        "ko": "RTCClient.on(channel-media-relay-state) 콜백을 기다리는 동안 오류가 발생했습니다.",
        "ja": "RTCClient.on(channel-media-relay-state)コールバックを待機中にエラーが発生しました。",
        "de": "Fehler beim Warten auf den RTCClient.on(channel-media-relay-state)-Rückruf.",
        "fr": "Erreur lors de l'attente du rappel RTCClient.on(channel-media-relay-state).",
        "id": "Kesalahan saat menunggu panggilan balik RTCClient.on(channel-media-relay-state).",
        "ru": "Ошибка при ожидании обратного вызова RTCClient.on(channel-media-relay-state)."
    },
    "CROSS_CHANNEL_FAILED_JOIN_SRC": {
        "zh": "发起跨频道转发媒体流请求失败。",
        "zh-CHT": "發起跨頻道轉發媒體流請求失敗。",
        "en": "Failed to initiate cross-channel media stream forwarding request.",
        "ko": "채널 간 미디어 스트림 전달 요청을 시작하지 못했습니다.",
        "ja": "チャンネル間メディアストリーム転送リクエストの開始に失敗しました。",
        "de": "Fehler beim Initiieren einer Anfrage zur Weiterleitung von Medienströmen zwischen Kanälen.",
        "fr": "Échec de l'initialisation de la demande de transfert de flux multimédia entre canaux.",
        "id": "Gagal memulai permintaan pengalihan aliran media antar saluran.",
        "ru": "Не удалось инициировать запрос на пересылку потока мультимедиа между каналами."
    },
    "CROSS_CHANNEL_FAILED_JOIN_DEST": {
        "zh": "接受跨频道转发媒体流请求失败。",
        "zh-CHT": "接受跨頻道轉發媒體流請求失敗。",
        "en": "Failed to accept cross-channel media stream forwarding request.",
        "ko": "채널 간 미디어 스트림 전달 요청을 수락하지 못했습니다.",
        "ja": "チャンネル間メディアストリーム転送リクエストの受け入れに失敗しました。",
        "de": "Fehler beim Akzeptieren einer Anfrage zur Weiterleitung von Medienströmen zwischen Kanälen.",
        "fr": "Échec de l'acceptation de la demande de transfert de flux multimédia entre canaux.",
        "id": "Gagal menerima permintaan pengalihan aliran media antar saluran.",
        "ru": "Не удалось принять запрос на пересылку потока мультимедиа между каналами."
    },
    "CROSS_CHANNEL_FAILED_PACKET_SENT_TO_DEST": {
        "zh": "服务器接收跨频道转发媒体流失败。",
        "zh-CHT": "服務器接收跨頻道轉發媒體流失敗。",
        "en": "Server failed to receive cross-channel forwarded media stream.",
        "ko": "서버가 채널 간 전달된 미디어 스트림을 수신하지 못했습니다.",
        "ja": "サーバーがチャンネル間転送されたメディアストリームの受信に失敗しました。",
        "de": "Server konnte den zwischen Kanälen weitergeleiteten Medienstrom nicht empfangen.",
        "fr": "Le serveur n'a pas pu recevoir le flux multimédia transféré entre canaux.",
        "id": "Server gagal menerima aliran media yang dikirimkan antar saluran.",
        "ru": "Сервер не смог принять поток мультимедиа, пересланный между каналами."
    },
    "CROSS_CHANNEL_SERVER_ERROR_RESPONSE": {
        "zh": "服务器响应出错。",
        "zh-CHT": "服務器響應出錯。",
        "en": "Server response error.",
        "ko": "서버 응답 오류입니다.",
        "ja": "サーバーのレスポンスエラーです。",
        "de": "Server-Antwortfehler.",
        "fr": "Erreur de réponse du serveur.",
        "id": "Kesalahan respons server.",
        "ru": "Ошибка ответа сервера."
    }
};

const getErrorMessage = (errorCode, language) => {
    const message = errorMessages[errorCode];
    if (message) {
        return message[language] || message['en'];
    }
    return null;
}

export {getErrorMessage}
