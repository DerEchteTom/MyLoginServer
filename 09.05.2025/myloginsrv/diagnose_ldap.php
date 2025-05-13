<?php
// Datei: diagnose_ldap.php – LDAP-Bind und Suche testen
date_default_timezone_set("Europe/Berlin");
require_once "mailer_config.php";

$env = parseEnvFile(__DIR__ . "/.envad");
$host = $env["AD_HOST"] ?? "";
$port = $env["AD_PORT"] ?? "389";
$base_dn = $env["AD_BASE_DN"] ?? "";
$attr = $env["AD_USER_ATTRIBUTE"] ?? "sAMAccountName";
$bind_dn = $env["AD_BIND_DN"] ?? "";
$bind_pw = $env["AD_BIND_PW"] ?? "";

$benutzernamen = ["administrator", "thomas.schmidt"];

echo "<h3>LDAP-Diagnose</h3>";
echo "<ul>";
echo "<li>Host: " . htmlspecialchars($host) . "</li>";
echo "<li>Port: " . htmlspecialchars($port) . "</li>";
echo "<li>Base DN: " . htmlspecialchars($base_dn) . "</li>";
echo "<li>Attribut: " . htmlspecialchars($attr) . "</li>";
echo "<li>Bind-DN: " . htmlspecialchars($bind_dn) . "</li>";
echo "</ul><hr>";

$ldap = ldap_connect($host, (int)$port);
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

if (!$ldap) {
    echo "<div style='color: red;'>❌ Verbindung zu LDAP-Server fehlgeschlagen.</div>";
    exit;
}

if (!@ldap_bind($ldap, $bind_dn, $bind_pw)) {
    echo "<div style='color:red;'>❌ LDAP-Bind fehlgeschlagen mit DN: $bind_dn</div>";
    exit;
} else {
    echo "<div style='color:green;'>✅ LDAP-Bind erfolgreich</div><hr>";
}

foreach ($benutzernamen as $user) {
    $filter = "($attr=" . ldap_escape($user, "", LDAP_ESCAPE_FILTER) . ")";
    echo "<strong>Suche: $filter</strong><br>";
    $search = @ldap_search($ldap, $base_dn, $filter, ["dn", "mail", "cn"]);
    if (!$search) {
        echo "<div style='color:red;'>❌ Suche fehlgeschlagen für Benutzer $user</div><hr>";
        continue;
    }

    $entries = ldap_get_entries($ldap, $search);
    if ($entries["count"] > 0) {
        echo "<div style='color:green;'>✅ Benutzer gefunden: <br>";
        echo "DN: " . htmlspecialchars($entries[0]["dn"]) . "<br>";
        echo "CN: " . htmlspecialchars($entries[0]["cn"][0] ?? "-") . "<br>";
        echo "Mail: " . htmlspecialchars($entries[0]["mail"][0] ?? "-") . "</div><hr>";
    } else {
        echo "<div style='color:red;'>❌ Kein Eintrag gefunden für $user</div><hr>";
    }
}

ldap_unbind($ldap);
?>
