let events = JSON.parse(localStorage.getItem("events")) || [];

function captureEvent(event) {
  const eventData = {
    type: event.type,
    timestamp: Date.now(),
    details: {
      target: getTargetDetails(event.target),
    },
  };

  if (event.type === "keydown") {
    eventData.details.keyPressed = event.key;
  } else if (event.type === "scroll") {
    eventData.details.scrollX = window.scrollX;
    eventData.details.scrollY = window.scrollY;
  } else {
    eventData.details.x = event.clientX;
    eventData.details.y = event.clientY;
  }

  events.push(eventData);

  // Store events in localStorage
  localStorage.setItem("events", JSON.stringify(events));
}

function getTargetDetails(target) {
  let details = target.tagName;
  if (target.id) {
    details += `#${target.id}`;
  }
  if (target.className) {
    details += `.${target.className.split(" ").join(".")}`;
  }
  return details;
}

async function sendEvents() {
  console.log(events);

  if (events.length > 0) {
    const dataToSend = [...events];
    events = [];
    localStorage.removeItem("events"); // Clear localStorage

    const baseUrl = window.location.origin;
    // const apiUrl = `${baseUrl}/php/sitevents-api.php`;
    const apiUrl = `${baseUrl}/siteevents/php/sitevents-api.php`;


    console.log(apiUrl);

    try {
      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(dataToSend),
      });

      if (!response.ok) {
        console.error("Failed to send events", response.statusText);
        events = dataToSend; // Restore events if sending fails
        localStorage.setItem("events", JSON.stringify(events)); // Restore events in localStorage
      }
    } catch (error) {
      console.error("Error sending events", error);
      events = dataToSend; // Restore events if sending fails
      localStorage.setItem("events", JSON.stringify(events)); // Restore events in localStorage
    }
  }
}

// Add event listeners
document.addEventListener("mousemove", captureEvent);
document.addEventListener("keydown", captureEvent);
document.addEventListener("click", captureEvent);
document.addEventListener("dblclick", captureEvent);
window.addEventListener("scroll", captureEvent);

// Send events every 10 seconds
setInterval(sendEvents, 10000);
