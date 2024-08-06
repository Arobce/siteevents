let events = [];

function captureEvent(event) {
    const eventData = {
        type: event.type,
        timestamp: Date.now(),
        details: {
            x: event.clientX || null,
            y: event.clientY || null,
            key: event.key || null,
        }
    };
    events.push(eventData);
}

document.addEventListener('mousemove', captureEvent);
document.addEventListener('keydown', captureEvent);

// Function to send events to the server
async function sendEvents() {
    console.log(events);

    if (events.length > 0) {
        const dataToSend = [...events];
        events = [];
        
        try {
            const response = await fetch('http://localhost/siteevents/php/sitevents-db.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dataToSend)
            });

            if (!response.ok) {
                console.error('Failed to send events', response.statusText);
            }
        } catch (error) {
            console.error('Error sending events', error);
        }
    }
}

// Send events every 10 seconds
setInterval(sendEvents, 10000);