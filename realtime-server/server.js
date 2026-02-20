require("dotenv").config();
const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const webpush = require("web-push");
const cors = require("cors");

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
  },
});

// Configure web-push
webpush.setVapidDetails(
  "mailto:admin@example.com",
  process.env.VAPID_PUBLIC_KEY,
  process.env.VAPID_PRIVATE_KEY,
);

const users = new Map(); // userId -> socketId

io.on("connection", (socket) => {
  console.log("A user connected:", socket.id);

  socket.on("register", (userId) => {
    users.set(userId.toString(), socket.id);
    console.log(`User ${userId} registered with socket ${socket.id}`);
  });

  socket.on("join_thread", (threadId) => {
    socket.join(`thread_${threadId}`);
    console.log(`Socket ${socket.id} joined thread_${threadId}`);
  });

  socket.on("typing", (data) => {
    const { threadId, userId, username, isTyping } = data;
    socket
      .to(`thread_${threadId}`)
      .emit("typing_status", { userId, username, isTyping });
  });

  socket.on("disconnect", () => {
    for (const [userId, socketId] of users.entries()) {
      if (socketId === socket.id) {
        users.delete(userId);
        break;
      }
    }
    console.log("User disconnected:", socket.id);
  });
});

// API for PHP to trigger events
app.post("/api/notify", (req, res) => {
  const { secret, type, data } = req.body;

  if (secret !== process.env.SECRET_KEY) {
    return res.status(403).json({ error: "Forbidden" });
  }

  if (type === "new_message") {
    const { threadId, message } = data;
    io.to(`thread_${threadId}`).emit("new_message", message);
  } else if (type === "new_group_message") {
    const { groupThreadId, message } = data;
    io.to(`group_${groupThreadId}`).emit("new_message", message);
  } else if (type === "new_dm") {
    const { receiverId, message } = data;
    const socketId = users.get(receiverId.toString());
    if (socketId) {
      io.to(socketId).emit("new_dm", message);
    }
  } else if (type === "typing") {
    const { threadId, userId, username, isTyping } = data;
    socket
      .to(`thread_${threadId}`)
      .emit("typing_status", { userId, username, isTyping });
  }

  res.json({ success: true });
});

// API for Push Notifications
app.post("/api/push", async (req, res) => {
  const { secret, subscription, payload } = req.body;

  if (secret !== process.env.SECRET_KEY) {
    return res.status(403).json({ error: "Forbidden" });
  }

  try {
    await webpush.sendNotification(subscription, JSON.stringify(payload));
    res.json({ success: true });
  } catch (err) {
    console.error("Push error:", err);
    res.status(500).json({ error: err.message });
  }
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Realtime server running on port ${PORT}`);
});
