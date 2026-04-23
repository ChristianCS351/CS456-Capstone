// I used this incredible source to help me find out how to ease things in and out using a combination of Javascript and HTML.
// Source: https://bobbyhadz.com/blog/javascript-hide-element-after-few-seconds

setTimeout(() => {
  const box = document.getElementById('box');
  box.style.display = 'none';
}, 10000); 

setTimeout(() => {
  const box = document.getElementById('box1');
  box.style.display = 'none';
}, 10000); 