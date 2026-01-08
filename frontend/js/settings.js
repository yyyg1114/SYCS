function saveSettings(){
fetch("../backend/settings.php",{
method:"POST",
headers:{'Content-Type':'application/json'},
body:JSON.stringify({
    theme:document.getElementById("theme").value
})
});
}
