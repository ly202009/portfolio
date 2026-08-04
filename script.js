const minScale = 0.5;
const maxScale = 1;
const minOpacity = 0.5;
const maxOpacity = 1;
var rotationSpeed = 0.002;

const container = document.querySelector(".about-icons");

function calculateCarousel(rotation, icon, total){
  // Use carousel rotation and icon position to calculate position of the icon;
  const localRotation = rotation + 2 * Math.PI * (icon.style.getPropertyValue("--i")/total);
  // Use sin to determine the position between one side to the other within the box as well as cosine for depth
  const depthRatio = (Math.cos(localRotation) + 1)/2;
  // const positionRatio = (Math.sin(localRotation) + 1)/2;

  // icon.style.left = `${positionRatio * 100}%`;

  const x = Math.sin(localRotation) * container.clientWidth / 2;

  icon.style.transform = `scale(${depthRatio * (maxScale - minScale) + minScale}) translate(-50%, 0) translate(${x}px, 0px)`;
  icon.style.zIndex = Math.floor(depthRatio * 100);
  icon.style.opacity = minOpacity + (maxOpacity - minOpacity) * depthRatio;
}

let rotation = 0; // initial rotation
const icons = document.querySelectorAll(".about-icon");

function animate(rotation){ // why was this so hard to figure out
  icons.forEach((icon) => calculateCarousel(rotation, icon, 10));
  requestAnimationFrame(() => animate(rotation+rotationSpeed));
}

animate(rotation);