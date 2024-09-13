function copySecretValue(id) {
    navigator.clipboard.writeText(document.getElementById(id).value)
}

function showSecretValue(id) {
    document.getElementById(id).type = document.getElementById(id).type === 'password' ? 'text' : 'password';
}
