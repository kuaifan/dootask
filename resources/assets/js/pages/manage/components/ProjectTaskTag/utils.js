const systemTags = {
    "zh": [
        {"name": "需求", "desc": "新功能或业务需求", "color": "#007bff"},
        {"name": "优化", "desc": "现有功能或体验的改进", "color": "#28a745"},
        {"name": "Bug", "desc": "问题或功能异常", "color": "#dc3545"},
        {"name": "设计", "desc": "与UI/UX设计相关的任务", "color": "#6f42c1"},
        {"name": "开发", "desc": "涉及前端或后端开发的任务", "color": "#17a2b8"},
        {"name": "测试", "desc": "测试相关任务", "color": "#fd7e14"},
        {"name": "文档", "desc": "与文档编写或维护相关的任务", "color": "#20c997"},
        {"name": "性能", "desc": "性能优化相关任务", "color": "#6610f2"},
        {"name": "安全", "desc": "与安全问题或漏洞相关的任务", "color": "#e83e8c"},
        {"name": "技术债务", "desc": "需要偿还的技术债务", "color": "#6c757d"},
        {"name": "实验性", "desc": "任务属于探索性质", "color": "#6f42c1"},
        {"name": "学习任务", "desc": "任务用于学习或研究", "color": "#20c997"},
        {"name": "紧急", "desc": "需要优先处理的任务", "color": "#d63384"},
        {"name": "高优先级", "desc": "优先级较高的任务", "color": "#ffc107"},
        {"name": "低优先级", "desc": "优先级较低的任务", "color": "#6c757d"},
        {"name": "无效", "desc": "任务被标记为无效", "color": "#adb5bd"},
        {"name": "重复", "desc": "任务与已有任务重复", "color": "#ced4da"},
        {"name": "不适用", "desc": "任务不再适用当前需求", "color": "#dee2e6"},
        {"name": "延期", "desc": "任务因某些原因被延期", "color": "#ffc107"},
        {"name": "阻塞", "desc": "任务被问题或依赖阻塞", "color": "#dc3545"},
        {"name": "待确认", "desc": "任务需要进一步确认", "color": "#0dcaf0"},
        {"name": "已搁置", "desc": "任务暂时搁置", "color": "#adb5bd"},
        {"name": "待复盘", "desc": "任务完成后需复盘", "color": "#17a2b8"},
        {"name": "外部依赖", "desc": "依赖外部资源的任务", "color": "#fd7e14"},
        {"name": "跨团队协作", "desc": "需要多个团队协作的任务", "color": "#17a2b8"},
        {"name": "研发支持", "desc": "为其他团队提供支持", "color": "#20c997"}
    ],

    "zh-CHT": [
        {"name": "需求", "desc": "新功能或業務需求", "color": "#007bff"},
        {"name": "優化", "desc": "現有功能或體驗的改進", "color": "#28a745"},
        {"name": "Bug", "desc": "功能異常或錯誤", "color": "#dc3545"},
        {"name": "設計", "desc": "與UI/UX設計相關的任務", "color": "#6f42c1"},
        {"name": "開發", "desc": "前後端開發相關任務", "color": "#17a2b8"},
        {"name": "測試", "desc": "功能測試或用例驗證", "color": "#fd7e14"},
        {"name": "文檔", "desc": "與文檔編寫或維護相關的任務", "color": "#20c997"},
        {"name": "性能", "desc": "性能優化相關任務", "color": "#6610f2"},
        {"name": "安全", "desc": "與安全問題或漏洞相關的任務", "color": "#e83e8c"},
        {"name": "技術債務", "desc": "需要償還的技術債務", "color": "#6c757d"},
        {"name": "實驗性", "desc": "任務屬於探索性質", "color": "#6f42c1"},
        {"name": "學習任務", "desc": "任務用於學習或研究", "color": "#20c997"},
        {"name": "緊急", "desc": "需要優先處理的任務", "color": "#d63384"},
        {"name": "高優先級", "desc": "優先級較高的任務", "color": "#ffc107"},
        {"name": "低優先級", "desc": "優先級較低的任務", "color": "#6c757d"},
        {"name": "無效", "desc": "任務無效，不再處理", "color": "#adb5bd"},
        {"name": "重複", "desc": "與其他任務重複", "color": "#ced4da"},
        {"name": "不適用", "desc": "任務不再適用當前需求", "color": "#dee2e6"},
        {"name": "延期", "desc": "任務因某些原因被延期", "color": "#ffc107"},
        {"name": "阻塞", "desc": "任務被問題或依賴阻塞", "color": "#dc3545"},
        {"name": "待確認", "desc": "任務信息不完整，需確認", "color": "#0dcaf0"},
        {"name": "已擱置", "desc": "任務暫停處理，未來可能重啟", "color": "#adb5bd"},
        {"name": "待復盤", "desc": "任務完成後需總結復盤", "color": "#17a2b8"},
        {"name": "外部依賴", "desc": "依賴外部資源的任務", "color": "#fd7e14"},
        {"name": "跨團隊協作", "desc": "需要多個團隊協作的任務", "color": "#17a2b8"},
        {"name": "研發支持", "desc": "為其他團隊提供支持", "color": "#20c997"}
    ],

    "en": [
        {"name": "Requirement", "desc": "New feature or business requirement", "color": "#007bff"},
        {"name": "Optimization", "desc": "Improvement of existing features or experience", "color": "#28a745"},
        {"name": "Bug", "desc": "Feature malfunction or error", "color": "#dc3545"},
        {"name": "Design", "desc": "Tasks related to UI/UX design", "color": "#6f42c1"},
        {"name": "Development", "desc": "Tasks related to frontend or backend development", "color": "#17a2b8"},
        {"name": "Testing", "desc": "Feature testing or case verification", "color": "#fd7e14"},
        {"name": "Documentation", "desc": "Tasks related to writing or maintaining documentation", "color": "#20c997"},
        {"name": "Performance", "desc": "Tasks related to performance optimization", "color": "#6610f2"},
        {"name": "Security", "desc": "Tasks related to security issues or vulnerabilities", "color": "#e83e8c"},
        {"name": "Technical Debt", "desc": "Technical debt that needs to be addressed", "color": "#6c757d"},
        {"name": "Experimental", "desc": "Tasks of an exploratory nature", "color": "#6f42c1"},
        {"name": "Learning Task", "desc": "Tasks for learning or research purposes", "color": "#20c997"},
        {"name": "Urgent", "desc": "Tasks that need to be prioritized", "color": "#d63384"},
        {"name": "High Priority", "desc": "Tasks with high priority", "color": "#ffc107"},
        {"name": "Low Priority", "desc": "Tasks with low priority", "color": "#6c757d"},
        {"name": "Invalid", "desc": "Task is invalid and will no longer be processed", "color": "#adb5bd"},
        {"name": "Duplicate", "desc": "Task is a duplicate of an existing one", "color": "#ced4da"},
        {"name": "Not Applicable", "desc": "Task is no longer applicable to current requirements", "color": "#dee2e6"},
        {"name": "Postponed", "desc": "Task is postponed for some reason", "color": "#ffc107"},
        {"name": "Blocked", "desc": "Task is blocked by issues or dependencies", "color": "#dc3545"},
        {"name": "To Be Confirmed", "desc": "Task information is incomplete and needs confirmation", "color": "#0dcaf0"},
        {"name": "On Hold", "desc": "Task is temporarily on hold and may resume in the future", "color": "#adb5bd"},
        {"name": "To Be Reviewed", "desc": "Task needs to be reviewed or summarized after completion", "color": "#17a2b8"},
        {"name": "External Dependency", "desc": "Task depends on external resources", "color": "#fd7e14"},
        {"name": "Cross-Team Collaboration", "desc": "Task requires collaboration across multiple teams", "color": "#17a2b8"},
        {"name": "R&D Support", "desc": "Providing support to other teams", "color": "#20c997"}
    ],

    "ko": [
        {"name": "요구사항", "desc": "새로운 기능 또는 비즈니스 요구사항", "color": "#007bff"},
        {"name": "최적화", "desc": "기존 기능 또는 경험의 개선", "color": "#28a745"},
        {"name": "버그", "desc": "기능 오작동 또는 오류", "color": "#dc3545"},
        {"name": "디자인", "desc": "UI/UX 디자인 관련 작업", "color": "#6f42c1"},
        {"name": "개발", "desc": "프론트엔드 또는 백엔드 개발 관련 작업", "color": "#17a2b8"},
        {"name": "테스트", "desc": "기능 테스트 또는 사례 검증", "color": "#fd7e14"},
        {"name": "문서화", "desc": "문서 작성 또는 유지보수 작업", "color": "#20c997"},
        {"name": "성능", "desc": "성능 최적화 관련 작업", "color": "#6610f2"},
        {"name": "보안", "desc": "보안 문제 또는 취약점 관련 작업", "color": "#e83e8c"},
        {"name": "기술 부채", "desc": "해결해야 할 기술 부채", "color": "#6c757d"},
        {"name": "실험적", "desc": "탐색적인 성격의 작업", "color": "#6f42c1"},
        {"name": "학습 작업", "desc": "학습 또는 연구를 위한 작업", "color": "#20c997"},
        {"name": "긴급", "desc": "우선적으로 처리해야 할 작업", "color": "#d63384"},
        {"name": "높은 우선순위", "desc": "우선순위가 높은 작업", "color": "#ffc107"},
        {"name": "낮은 우선순위", "desc": "우선순위가 낮은 작업", "color": "#6c757d"},
        {"name": "무효", "desc": "작업이 무효화되어 더 이상 처리되지 않음", "color": "#adb5bd"},
        {"name": "중복", "desc": "기존 작업과 중복된 작업", "color": "#ced4da"},
        {"name": "부적합", "desc": "현재 요구사항에 더 이상 적합하지 않은 작업", "color": "#dee2e6"},
        {"name": "연기됨", "desc": "특정 이유로 연기된 작업", "color": "#ffc107"},
        {"name": "차단됨", "desc": "문제 또는 의존성으로 인해 차단된 작업", "color": "#dc3545"},
        {"name": "확인 필요", "desc": "정보가 불완전하여 확인이 필요한 작업", "color": "#0dcaf0"},
        {"name": "보류 중", "desc": "작업이 일시적으로 보류되었으며, 추후 재개될 수 있음", "color": "#adb5bd"},
        {"name": "리뷰 필요", "desc": "작업 완료 후 요약 또는 리뷰가 필요한 작업", "color": "#17a2b8"},
        {"name": "외부 의존성", "desc": "외부 리소스에 의존하는 작업", "color": "#fd7e14"},
        {"name": "팀 간 협업", "desc": "다수의 팀이 협업해야 하는 작업", "color": "#17a2b8"},
        {"name": "개발 지원", "desc": "다른 팀에 지원을 제공하는 작업", "color": "#20c997"}
    ],

    "ja": [
        {"name": "要件", "desc": "新しい機能またはビジネス要件", "color": "#007bff"},
        {"name": "最適化", "desc": "既存の機能または体験の改善", "color": "#28a745"},
        {"name": "バグ", "desc": "機能の不具合またはエラー", "color": "#dc3545"},
        {"name": "デザイン", "desc": "UI/UXデザインに関連するタスク", "color": "#6f42c1"},
        {"name": "開発", "desc": "フロントエンドまたはバックエンド開発に関するタスク", "color": "#17a2b8"},
        {"name": "テスト", "desc": "機能テストまたはケース検証", "color": "#fd7e14"},
        {"name": "ドキュメント", "desc": "ドキュメントの作成または保守に関連するタスク", "color": "#20c997"},
        {"name": "パフォーマンス", "desc": "パフォーマンス最適化に関連するタスク", "color": "#6610f2"},
        {"name": "セキュリティ", "desc": "セキュリティ問題または脆弱性に関連するタスク", "color": "#e83e8c"},
        {"name": "技術的負債", "desc": "解消が必要な技術的負債", "color": "#6c757d"},
        {"name": "実験的", "desc": "探索的な性質のタスク", "color": "#6f42c1"},
        {"name": "学習タスク", "desc": "学習または研究を目的としたタスク", "color": "#20c997"},
        {"name": "緊急", "desc": "優先的に処理が必要なタスク", "color": "#d63384"},
        {"name": "高優先度", "desc": "優先度の高いタスク", "color": "#ffc107"},
        {"name": "低優先度", "desc": "優先度の低いタスク", "color": "#6c757d"},
        {"name": "無効", "desc": "タスクが無効で、処理されなくなった", "color": "#adb5bd"},
        {"name": "重複", "desc": "既存のタスクと重複しているタスク", "color": "#ced4da"},
        {"name": "不適用", "desc": "現在の要件に適用されなくなったタスク", "color": "#dee2e6"},
        {"name": "延期", "desc": "何らかの理由で延期されたタスク", "color": "#ffc107"},
        {"name": "ブロック中", "desc": "問題または依存関係によってブロックされたタスク", "color": "#dc3545"},
        {"name": "要確認", "desc": "情報が不完全で確認が必要なタスク", "color": "#0dcaf0"},
        {"name": "保留中", "desc": "タスクが一時的に保留され、将来的に再開される可能性がある", "color": "#adb5bd"},
        {"name": "レビュー待ち", "desc": "タスク完了後に要約またはレビューが必要なタスク", "color": "#17a2b8"},
        {"name": "外部依存", "desc": "外部リソースに依存するタスク", "color": "#fd7e14"},
        {"name": "チーム間協力", "desc": "複数のチームが協力する必要があるタスク", "color": "#17a2b8"},
        {"name": "開発サポート", "desc": "他のチームにサポートを提供するタスク", "color": "#20c997"}
    ],

    "de": [
        {"name": "Anforderung", "desc": "Neue Funktion oder geschäftliche Anforderung", "color": "#007bff"},
        {"name": "Optimierung", "desc": "Verbesserung bestehender Funktionen oder Erfahrungen", "color": "#28a745"},
        {"name": "Bug", "desc": "Funktionsfehler oder Problem", "color": "#dc3545"},
        {"name": "Design", "desc": "Aufgaben im Zusammenhang mit UI/UX-Design", "color": "#6f42c1"},
        {"name": "Entwicklung", "desc": "Aufgaben im Bereich Frontend- oder Backend-Entwicklung", "color": "#17a2b8"},
        {"name": "Testen", "desc": "Funktionstests oder Fallüberprüfungen", "color": "#fd7e14"},
        {"name": "Dokumentation", "desc": "Aufgaben zur Erstellung oder Pflege von Dokumentationen", "color": "#20c997"},
        {"name": "Leistung", "desc": "Aufgaben zur Leistungsoptimierung", "color": "#6610f2"},
        {"name": "Sicherheit", "desc": "Aufgaben im Zusammenhang mit Sicherheitsproblemen oder Schwachstellen", "color": "#e83e8c"},
        {"name": "Technische Schulden", "desc": "Technische Schulden, die abgebaut werden müssen", "color": "#6c757d"},
        {"name": "Experimentell", "desc": "Aufgaben explorativer Natur", "color": "#6f42c1"},
        {"name": "Lernaufgabe", "desc": "Aufgaben zum Lernen oder für Forschungszwecke", "color": "#20c997"},
        {"name": "Dringend", "desc": "Aufgaben, die vorrangig bearbeitet werden müssen", "color": "#d63384"},
        {"name": "Hohe Priorität", "desc": "Aufgaben mit hoher Priorität", "color": "#ffc107"},
        {"name": "Niedrige Priorität", "desc": "Aufgaben mit niedriger Priorität", "color": "#6c757d"},
        {"name": "Ungültig", "desc": "Aufgabe ist ungültig und wird nicht weiter bearbeitet", "color": "#adb5bd"},
        {"name": "Duplikat", "desc": "Aufgabe ist ein Duplikat einer bestehenden Aufgabe", "color": "#ced4da"},
        {"name": "Nicht anwendbar", "desc": "Aufgabe ist für die aktuellen Anforderungen nicht mehr relevant", "color": "#dee2e6"},
        {"name": "Verschoben", "desc": "Aufgabe wurde aus bestimmten Gründen verschoben", "color": "#ffc107"},
        {"name": "Blockiert", "desc": "Aufgabe ist durch Probleme oder Abhängigkeiten blockiert", "color": "#dc3545"},
        {"name": "Zu bestätigen", "desc": "Aufgabe ist unvollständig und muss bestätigt werden", "color": "#0dcaf0"},
        {"name": "In Wartestellung", "desc": "Aufgabe ist vorübergehend pausiert und könnte später wieder aufgenommen werden", "color": "#adb5bd"},
        {"name": "Zu überprüfen", "desc": "Aufgabe muss nach Abschluss überprüft oder zusammengefasst werden", "color": "#17a2b8"},
        {"name": "Externe Abhängigkeit", "desc": "Aufgabe ist von externen Ressourcen abhängig", "color": "#fd7e14"},
        {"name": "Teamübergreifende Zusammenarbeit", "desc": "Aufgabe erfordert Zusammenarbeit mehrerer Teams", "color": "#17a2b8"},
        {"name": "Entwicklungsunterstützung", "desc": "Aufgabe zur Unterstützung anderer Teams", "color": "#20c997"}
    ],

    "fr": [
        {"name": "Exigence", "desc": "Nouvelle fonctionnalité ou exigence métier", "color": "#007bff"},
        {"name": "Optimisation", "desc": "Amélioration des fonctionnalités ou de l'expérience existante", "color": "#28a745"},
        {"name": "Bug", "desc": "Dysfonctionnement ou erreur", "color": "#dc3545"},
        {"name": "Conception", "desc": "Tâches liées à la conception UI/UX", "color": "#6f42c1"},
        {"name": "Développement", "desc": "Tâches liées au développement frontend ou backend", "color": "#17a2b8"},
        {"name": "Test", "desc": "Tests fonctionnels ou vérifications de cas", "color": "#fd7e14"},
        {"name": "Documentation", "desc": "Tâches de rédaction ou de maintenance de la documentation", "color": "#20c997"},
        {"name": "Performance", "desc": "Tâches liées à l'optimisation des performances", "color": "#6610f2"},
        {"name": "Sécurité", "desc": "Tâches liées aux problèmes ou vulnérabilités de sécurité", "color": "#e83e8c"},
        {"name": "Dette technique", "desc": "Dette technique à résoudre", "color": "#6c757d"},
        {"name": "Expérimental", "desc": "Tâches de nature exploratoire", "color": "#6f42c1"},
        {"name": "Tâche d'apprentissage", "desc": "Tâches pour apprentissage ou recherche", "color": "#20c997"},
        {"name": "Urgent", "desc": "Tâches nécessitant un traitement prioritaire", "color": "#d63384"},
        {"name": "Haute priorité", "desc": "Tâches avec une priorité élevée", "color": "#ffc107"},
        {"name": "Basse priorité", "desc": "Tâches avec une priorité basse", "color": "#6c757d"},
        {"name": "Invalide", "desc": "Tâche invalide qui ne sera plus traitée", "color": "#adb5bd"},
        {"name": "Dupliqué", "desc": "Tâche en double avec une autre existante", "color": "#ced4da"},
        {"name": "Non applicable", "desc": "Tâche non applicable aux exigences actuelles", "color": "#dee2e6"},
        {"name": "Reporté", "desc": "Tâche reportée pour une raison quelconque", "color": "#ffc107"},
        {"name": "Bloqué", "desc": "Tâche bloquée par des problèmes ou des dépendances", "color": "#dc3545"},
        {"name": "À confirmer", "desc": "Tâche incomplète nécessitant une confirmation", "color": "#0dcaf0"},
        {"name": "En attente", "desc": "Tâche temporairement suspendue, pouvant être reprise plus tard", "color": "#adb5bd"},
        {"name": "À revoir", "desc": "Tâche nécessitant un résumé ou une révision après achèvement", "color": "#17a2b8"},
        {"name": "Dépendance externe", "desc": "Tâche dépendant de ressources externes", "color": "#fd7e14"},
        {"name": "Collaboration inter-équipes", "desc": "Tâche nécessitant la collaboration de plusieurs équipes", "color": "#17a2b8"},
        {"name": "Support développement", "desc": "Tâche de support pour d'autres équipes", "color": "#20c997"}
    ],

    "id": [
        {"name": "Kebutuhan", "desc": "Fitur baru atau kebutuhan bisnis", "color": "#007bff"},
        {"name": "Optimalisasi", "desc": "Peningkatan fitur atau pengalaman yang ada", "color": "#28a745"},
        {"name": "Bug", "desc": "Malfungsi fitur atau kesalahan", "color": "#dc3545"},
        {"name": "Desain", "desc": "Tugas terkait desain UI/UX", "color": "#6f42c1"},
        {"name": "Pengembangan", "desc": "Tugas terkait pengembangan frontend atau backend", "color": "#17a2b8"},
        {"name": "Pengujian", "desc": "Pengujian fitur atau verifikasi kasus", "color": "#fd7e14"},
        {"name": "Dokumentasi", "desc": "Tugas terkait penulisan atau pemeliharaan dokumentasi", "color": "#20c997"},
        {"name": "Performa", "desc": "Tugas terkait optimalisasi performa", "color": "#6610f2"},
        {"name": "Keamanan", "desc": "Tugas terkait masalah atau kerentanan keamanan", "color": "#e83e8c"},
        {"name": "Hutang Teknis", "desc": "Hutang teknis yang perlu diselesaikan", "color": "#6c757d"},
        {"name": "Eksperimental", "desc": "Tugas yang bersifat eksplorasi", "color": "#6f42c1"},
        {"name": "Tugas Pembelajaran", "desc": "Tugas untuk pembelajaran atau penelitian", "color": "#20c997"},
        {"name": "Mendesak", "desc": "Tugas yang perlu diprioritaskan", "color": "#d63384"},
        {"name": "Prioritas Tinggi", "desc": "Tugas dengan prioritas tinggi", "color": "#ffc107"},
        {"name": "Prioritas Rendah", "desc": "Tugas dengan prioritas rendah", "color": "#6c757d"},
        {"name": "Tidak Valid", "desc": "Tugas tidak valid dan tidak akan diproses lagi", "color": "#adb5bd"},
        {"name": "Duplikat", "desc": "Tugas yang merupakan duplikat dari tugas lain", "color": "#ced4da"},
        {"name": "Tidak Berlaku", "desc": "Tugas tidak lagi relevan dengan kebutuhan saat ini", "color": "#dee2e6"},
        {"name": "Ditunda", "desc": "Tugas yang ditunda karena alasan tertentu", "color": "#ffc107"},
        {"name": "Terblokir", "desc": "Tugas yang terhalang oleh masalah atau ketergantungan", "color": "#dc3545"},
        {"name": "Perlu Konfirmasi", "desc": "Tugas yang informasinya tidak lengkap dan perlu konfirmasi", "color": "#0dcaf0"},
        {"name": "Ditangguhkan", "desc": "Tugas yang ditangguhkan sementara dan mungkin dilanjutkan di masa depan", "color": "#adb5bd"},
        {"name": "Perlu Ditinjau", "desc": "Tugas yang perlu ditinjau atau dirangkum setelah selesai", "color": "#17a2b8"},
        {"name": "Ketergantungan Eksternal", "desc": "Tugas yang bergantung pada sumber daya eksternal", "color": "#fd7e14"},
        {"name": "Kolaborasi Antar Tim", "desc": "Tugas yang membutuhkan kolaborasi beberapa tim", "color": "#17a2b8"},
        {"name": "Dukungan Pengembangan", "desc": "Tugas untuk mendukung tim lain", "color": "#20c997"}
    ],

    "ru": [
        {"name": "Требование", "desc": "Новая функция или бизнес-требование", "color": "#007bff"},
        {"name": "Оптимизация", "desc": "Улучшение существующих функций или опыта", "color": "#28a745"},
        {"name": "Баг", "desc": "Ошибка или неисправность функции", "color": "#dc3545"},
        {"name": "Дизайн", "desc": "Задачи, связанные с дизайном UI/UX", "color": "#6f42c1"},
        {"name": "Разработка", "desc": "Задачи, связанные с разработкой фронтенда или бэкенда", "color": "#17a2b8"},
        {"name": "Тестирование", "desc": "Тестирование функций или проверка кейсов", "color": "#fd7e14"},
        {"name": "Документация", "desc": "Задачи, связанные с написанием или поддержкой документации", "color": "#20c997"},
        {"name": "Производительность", "desc": "Задачи по оптимизации производительности", "color": "#6610f2"},
        {"name": "Безопасность", "desc": "Задачи, связанные с проблемами безопасности или уязвимостями", "color": "#e83e8c"},
        {"name": "Технический долг", "desc": "Технический долг, который нужно устранить", "color": "#6c757d"},
        {"name": "Экспериментальный", "desc": "Задачи исследовательского характера", "color": "#6f42c1"},
        {"name": "Обучающая задача", "desc": "Задачи для обучения или исследований", "color": "#20c997"},
        {"name": "Срочно", "desc": "Задачи, требующие приоритетного выполнения", "color": "#d63384"},
        {"name": "Высокий приоритет", "desc": "Задачи с высоким приоритетом", "color": "#ffc107"},
        {"name": "Низкий приоритет", "desc": "Задачи с низким приоритетом", "color": "#6c757d"},
        {"name": "Недействительно", "desc": "Задача недействительна и больше не будет обрабатываться", "color": "#adb5bd"},
        {"name": "Дубликат", "desc": "Задача дублирует существующую", "color": "#ced4da"},
        {"name": "Неприменимо", "desc": "Задача больше не актуальна для текущих требований", "color": "#dee2e6"},
        {"name": "Отложено", "desc": "Задача отложена по какой-либо причине", "color": "#ffc107"},
        {"name": "Заблокировано", "desc": "Задача заблокирована проблемами или зависимостями", "color": "#dc3545"},
        {"name": "Требует подтверждения", "desc": "Задача неполная и требует подтверждения", "color": "#0dcaf0"},
        {"name": "На удержании", "desc": "Задача временно приостановлена и может быть возобновлена позже", "color": "#adb5bd"},
        {"name": "Требует проверки", "desc": "Задача требует проверки или подведения итогов после завершения", "color": "#17a2b8"},
        {"name": "Внешняя зависимость", "desc": "Задача зависит от внешних ресурсов", "color": "#fd7e14"},
        {"name": "Межкомандное сотрудничество", "desc": "Задача требует сотрудничества нескольких команд", "color": "#17a2b8"},
        {"name": "Поддержка разработки", "desc": "Задача по поддержке других команд", "color": "#20c997"}
    ]
}

const colorUtils = {
    cache: new Map(),

    // 清理缓存
    clearCache() {
        if (this.cache.size > 1000) {
            this.cache.clear();
        }
    },

    // 判断颜色是否为深色
    isColorDark(color) {
        if (!color) return true;

        const cacheKey = `dark_${color}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16) | 0;
        const g = parseInt(hex.substr(2, 2), 16) | 0;
        const b = parseInt(hex.substr(4, 2), 16) | 0;

        const brightness = (r * 299 + g * 587 + b * 114) >> 10;
        const isDark = brightness < 128;

        this.cache.set(cacheKey, isDark);
        return isDark;
    },

    // 将 hex 转换为 HSL
    hexToHSL(hex) {
        if (!hex || typeof hex !== 'string') return { h: 0, s: 0, l: 0 };

        const cacheKey = `hsl_${hex}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        if (!result) return { h: 0, s: 0, l: 0 };

        const r = (parseInt(result[1], 16) | 0) / 255;
        const g = (parseInt(result[2], 16) | 0) / 255;
        const b = (parseInt(result[3], 16) | 0) / 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;

        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            const hueCalc = {
                [r]: () => (g - b) / d + (g < b ? 6 : 0),
                [g]: () => (b - r) / d + 2,
                [b]: () => (r - g) / d + 4
            };
            h = hueCalc[max]() / 6;
        }

        const hsl = {
            h: (h * 360) | 0,
            s: (s * 100) | 0,
            l: (l * 100) | 0
        };

        this.cache.set(cacheKey, hsl);
        return hsl;
    },

    // HSL 转 hex
    HSLToHex(h, s, l) {
        s /= 100;
        l /= 100;
        const k = n => (n + h / 30) % 12;
        const a = s * Math.min(l, 1 - l);
        const f = n => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
        const toHex = x => {
            const hex = Math.round(x * 255).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        };

        return `#${toHex(f(0))}${toHex(f(8))}${toHex(f(4))}`;
    },

    // 生成配色方案
    generateColorScheme(baseColor, defaultColor = '#3498db') {
        if (!baseColor) baseColor = defaultColor;

        const cacheKey = `scheme_${baseColor}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        const hsl = this.hexToHSL(baseColor);
        const h = hsl.h;
        const s = hsl.s;
        const l = hsl.l;

        const colors = [
            baseColor,
            this.HSLToHex(h, s, Math.min(l + 20, 100)),
            this.HSLToHex(h, s, Math.max(l - 20, 0)),
            this.HSLToHex((h + 30) % 360, s, l),
            this.HSLToHex((h - 30 + 360) % 360, s, l)
        ];

        this.cache.set(cacheKey, colors);
        return colors;
    }
}

export {systemTags, colorUtils}
