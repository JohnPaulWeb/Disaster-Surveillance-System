let button = document.getElementById("btn");
let button2 = document.getElementById("btn2");
let button3 = document.getElementById("btn3");
let button4 = document.getElementById("getter");

button.addEventListener("click", function() {
   window.location.href = "weather.php";
});

button2.addEventListener("click", function() {
   window.location.href = "evac.php";
});

button3.addEventListener("click", function() {
   window.location.href = "threats.php";
});
