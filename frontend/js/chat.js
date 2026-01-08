let thread = document.getElementById("thread");
let chat = document.getElementById("chat");

fetch("../backend/thread.php")
.then(r=>r.json())
.then(t=>{
t.forEach(x=>{
let o=document.createElement("option");
o.value=x.id; o.text=x.title;
thread.appendChild(o);
});
});

function load(){
fetch("../backend/chat.php?thread_id="+thread.value)
.then(r=>r.json())
.then(d=>{
chat.innerHTML="";
d.forEach(m=>{
    chat.innerHTML+=`<p><b>${m.username}</b>: ${m.content}</p>`;
});
});
}

thread.onchange = load;

function send(){
fetch("../backend/chat.php",{
method:"POST",
body:new URLSearchParams({
    thread_id:thread.value,
    content:msg.value
})
}).then(load);
}
