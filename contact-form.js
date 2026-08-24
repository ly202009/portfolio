document.getElementById("contactForm").addEventListener("submit", async function(event){
  event.preventDefault();

  const form = this;
  const status = document.getElementById("status");
  status.textContent = "";
  status.style.color = "red";

  const formData = new FormData(form);

  let regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/; // valid email regex

  if(formData.get("name").trim() === ""){ // Name is not empty
    status.innerHTML += "Name is missing.<br>";
  }
  
  if(!regex.test(formData.get("email"))){ // Email fits with regex expectations for a normal email address
    status.innerHTML += "Email is not valid.<br>";
  }

  if(formData.get("subject").trim() === ""){ // Subject is included
    status.innerHTML += "Must include a subject.<br>";
  }
  
  if(formData.get("message").trim() === ""){ // Message is included
    status.innerHTML += "Must include a message.<br>";
  }

  if(status.textContent !== ""){
    return;
  }


  try{
    const response = await fetch("contact-form-handler.php", {
      method: "POST",
      body: formData
    });

    if(!response.ok){
      throw new Error("Server error");
    }

    const result = await response.text();

    status.style.color = "green";
    status.textContent = "Message sent!";

    // Clear original message (but keep name and email)
    let name = formData.get("name");
    let email = formData.get("email");
    form.reset();
    document.getElementById("name").value = name;
    document.getElementById("email").value = email;
  }catch (error){
    status.style.color = "red";
    status.textContent = "Something went wrong.";
    console.error(error);
  }
});