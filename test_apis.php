<!DOCTYPE html>
<html>
<head>
    <title>Test APIs</title>
</head>
<body>
    <h1>Test APIs StudyMate</h1>
    
    <h2>Test System Stats</h2>
    <button onclick="testStats()">Test Stats API</button>
    <div id="stats-result"></div>
    
    <h2>Test Organizations</h2>
    <button onclick="testOrgs()">Test Organizations API</button>
    <div id="orgs-result"></div>
    
    <h2>Test Users</h2>
    <button onclick="testUsers()">Test Users API</button>
    <div id="users-result"></div>
    
    <script>
        async function testStats() {
            try {
                const response = await fetch('system_stats.php?user_id=1');
                const data = await response.json();
                document.getElementById('stats-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('stats-result').innerHTML = 'Error: ' + error.message;
            }
        }
        
        async function testOrgs() {
            try {
                const response = await fetch('org_api.php?action=list&user_id=1');
                const data = await response.json();
                document.getElementById('orgs-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('orgs-result').innerHTML = 'Error: ' + error.message;
            }
        }
        
        async function testUsers() {
            try {
                const response = await fetch('users_api.php?action=list&user_id=1');
                const data = await response.json();
                document.getElementById('users-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('users-result').innerHTML = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>