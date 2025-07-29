const videoPlayer = document.getElementById("videoPlayer")
const runningText = document.getElementById("runningText")
const debugInfo = document.getElementById("debugInfo")
const debugContent = document.getElementById("debugContent")

let currentPlaylist = []
let currentVideoIndex = 0
let currentBannerText = null
let lastFetchTime = 0
const FETCH_INTERVAL = 10000 // 10 seconds
let retryCount = 0
const MAX_RETRIES = 3
let debugMode = false
let isHandlingError = false // Prevent infinite error loops

// Improved error handling and logging
function logMessage(message, type = "info") {
  const timestamp = new Date().toISOString()
  console[type](`[${timestamp}] ${message}`)

  // Update debug info if in debug mode
  if (debugMode) {
    updateDebugInfo(message)
  }
}

function updateDebugInfo(message) {
  const timestamp = new Date().toLocaleTimeString()
  debugContent.innerHTML = `
    <strong>Debug Info</strong><br>
    <small>Last Update: ${timestamp}</small><br>
    <div style="max-height: 200px; overflow-y: auto;">
      ${message}<br>
      Current Playlist: ${JSON.stringify(currentPlaylist)}<br>
      Current Index: ${currentVideoIndex}<br>
      Video Source: ${videoPlayer.src}<br>
      Video Ready State: ${videoPlayer.readyState}<br>
      Video Error: ${videoPlayer.error ? videoPlayer.error.message : "None"}<br>
      Is Handling Error: ${isHandlingError}
    </div>
  `
}

// Toggle debug mode with 'D' key
document.addEventListener("keydown", (e) => {
  if (e.key.toLowerCase() === "d") {
    debugMode = !debugMode
    debugInfo.style.display = debugMode ? "block" : "none"
    if (debugMode) {
      updateDebugInfo("Debug mode enabled")
    }
  }
})

async function checkVideoFile(videoPath) {
  try {
    const response = await fetch(`index.php?action=checkVideoFile&path=${encodeURIComponent(videoPath)}`)
    const data = await response.json()
    logMessage(`File check for ${videoPath}: ${data.exists ? "EXISTS" : "NOT FOUND"}`)
    if (data.exists) {
      logMessage(`File size: ${data.fileSize} bytes`)
    } else {
      logMessage(`Checked paths: ${JSON.stringify(data.checkedPaths)}`)
    }
    return data.exists
  } catch (error) {
    logMessage(`Error checking file ${videoPath}: ${error.message}`, "error")
    return false
  }
}

async function fetchCurrentVideoPlaylist() {
  try {
    logMessage("Fetching video playlist...")
    const response = await fetch("index.php?action=getCurrentVideoPlaylist", {
      method: "GET",
      headers: {
        "Cache-Control": "no-cache",
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    logMessage("Received playlist data: " + JSON.stringify(data))

    if (data.error) {
      logMessage("Error fetching video playlist: " + data.error, "error")
      return []
    }

    retryCount = 0 // Reset retry count on success
    const playlist = data.playlist || []

    // Check if files exist (only for non-empty playlist)
    if (playlist.length > 0) {
      for (const videoPath of playlist) {
        await checkVideoFile(videoPath)
      }
    }

    logMessage("Final playlist: " + JSON.stringify(playlist))
    return playlist
  } catch (error) {
    logMessage("Failed to fetch current video playlist: " + error.message, "error")
    retryCount++

    if (retryCount < MAX_RETRIES) {
      logMessage(`Retrying... (${retryCount}/${MAX_RETRIES})`, "warn")
      setTimeout(() => fetchCurrentVideoPlaylist(), 2000)
    }

    return []
  }
}

async function fetchCurrentBanner() {
  try {
    const response = await fetch("index.php?action=getCurrentBanner", {
      method: "GET",
      headers: {
        "Cache-Control": "no-cache",
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    if (data.error) {
      logMessage("Error fetching banner: " + data.error, "error")
      return null
    }

    return data.bannerText || null
  } catch (error) {
    logMessage("Failed to fetch current banner: " + error.message, "error")
    return null
  }
}

async function updatePlayer() {
  const now = Date.now()
  const currentTime = new Date().toLocaleTimeString()
  const currentDate = new Date().toLocaleDateString()

  logMessage(`Update player called at ${currentDate} ${currentTime}`)

  // Only fetch new data if enough time has passed
  if (now - lastFetchTime > FETCH_INTERVAL) {
    logMessage("Fetching new data...")

    const [newPlaylist, newBannerText] = await Promise.all([fetchCurrentVideoPlaylist(), fetchCurrentBanner()])

    logMessage("Current playlist: " + JSON.stringify(currentPlaylist))
    logMessage("New playlist: " + JSON.stringify(newPlaylist))

    // Handle playlist changes
    if (JSON.stringify(newPlaylist) !== JSON.stringify(currentPlaylist)) {
      logMessage("Playlist changed!")
      logMessage("Old: " + JSON.stringify(currentPlaylist))
      logMessage("New: " + JSON.stringify(newPlaylist))
      currentPlaylist = newPlaylist
      currentVideoIndex = 0
      isHandlingError = false // Reset error handling flag
      await playNextVideoInPlaylist()
    } else if (currentPlaylist.length > 0 && videoPlayer.paused && !videoPlayer.ended) {
      // Resume paused video if playlist hasn't changed
      try {
        await videoPlayer.play()
        logMessage("Resumed paused video")
      } catch (e) {
        logMessage("Error resuming video: " + e.message, "error")
      }
    } else if (currentPlaylist.length === 0) {
      // No videos scheduled - stop the error loop
      if (!isHandlingError) {
        videoPlayer.pause()
        videoPlayer.src = ""
        videoPlayer.load()
        logMessage("No video scheduled. Player paused.")
        runningText.textContent = "No video scheduled for this time."
        isHandlingError = true // Prevent further error handling
      }
    } else {
      logMessage("Playlist unchanged, no action needed")
    }

    // Handle banner changes
    if (newBannerText !== currentBannerText) {
      logMessage("Changing banner from: '" + currentBannerText + "' to: '" + newBannerText + "'")
      runningText.textContent = newBannerText || "No banner scheduled."
      currentBannerText = newBannerText

      // Restart animation
      runningText.style.animation = "none"
      void runningText.offsetWidth // Trigger reflow
      runningText.style.animation = null
    }

    lastFetchTime = now
  } else {
    logMessage("Skipping fetch - not enough time passed")
  }
}

async function playNextVideoInPlaylist() {
  if (currentPlaylist.length === 0) {
    if (!isHandlingError) {
      videoPlayer.pause()
      videoPlayer.src = ""
      videoPlayer.load()
      logMessage("Playlist is empty. No video to play.")
      runningText.textContent = "No video scheduled for this time."
      isHandlingError = true
    }
    return
  }

  const nextVideoPath = currentPlaylist[currentVideoIndex]
  const fullVideoUrl = window.location.origin + nextVideoPath

  logMessage(`Attempting to play video: ${nextVideoPath}`)
  logMessage(`Full URL: ${fullVideoUrl}`)

  if (videoPlayer.src !== fullVideoUrl) {
    try {
      isHandlingError = false // Reset error flag when trying new video

      // Show loading indicator
      document.getElementById("loadingIndicator").style.display = "block"
      document.getElementById("errorMessage").style.display = "none"

      // Check if file exists before trying to play
      const fileExists = await checkVideoFile(nextVideoPath)
      if (!fileExists) {
        throw new Error(`Video file not found: ${nextVideoPath}`)
      }

      videoPlayer.src = fullVideoUrl
      videoPlayer.load()

      // Wait for video to be ready before playing
      await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          reject(new Error("Video loading timeout"))
        }, 10000) // 10 second timeout

        const onCanPlay = () => {
          clearTimeout(timeout)
          videoPlayer.removeEventListener("canplay", onCanPlay)
          videoPlayer.removeEventListener("error", onError)
          document.getElementById("loadingIndicator").style.display = "none"
          resolve()
        }

        const onError = (e) => {
          clearTimeout(timeout)
          videoPlayer.removeEventListener("canplay", onCanPlay)
          videoPlayer.removeEventListener("error", onError)
          document.getElementById("loadingIndicator").style.display = "none"

          let errorMsg = "Unknown video error"
          if (videoPlayer.error) {
            switch (videoPlayer.error.code) {
              case 1:
                errorMsg = "Video loading aborted"
                break
              case 2:
                errorMsg = "Network error"
                break
              case 3:
                errorMsg = "Video decode error"
                break
              case 4:
                errorMsg = "Video format not supported"
                break
            }
          }
          reject(new Error(errorMsg))
        }

        videoPlayer.addEventListener("canplay", onCanPlay)
        videoPlayer.addEventListener("error", onError)
      })

      await videoPlayer.play()
      logMessage("Video started successfully")
      isHandlingError = false
    } catch (e) {
      logMessage("Error playing video: " + e.message, "error")
      document.getElementById("loadingIndicator").style.display = "none"
      document.getElementById("errorMessage").style.display = "block"
      document.getElementById("errorMessage").textContent = `Error: ${e.message}`

      // Try next video after delay, but prevent infinite loops
      if (!isHandlingError) {
        isHandlingError = true
        setTimeout(() => {
          handleVideoError()
        }, 3000)
      }
    }
  } else {
    // Same video, just ensure it's playing
    if (videoPlayer.paused && !videoPlayer.ended) {
      try {
        await videoPlayer.play()
        logMessage("Resumed current video")
      } catch (e) {
        logMessage("Error resuming current video: " + e.message, "error")
      }
    }
  }
}

function handleVideoError() {
  if (isHandlingError) {
    return // Prevent multiple error handling
  }

  isHandlingError = true
  logMessage("Handling video error, trying next video...", "warn")
  runningText.textContent = "Error playing video. Trying next..."

  currentVideoIndex++
  if (currentVideoIndex >= currentPlaylist.length) {
    currentVideoIndex = 0
  }

  // Try next video after a short delay
  setTimeout(() => {
    isHandlingError = false
    playNextVideoInPlaylist()
  }, 2000)
}

// Event Listeners
videoPlayer.addEventListener("ended", () => {
  logMessage("Video ended. Moving to next in playlist.")
  currentVideoIndex++
  if (currentVideoIndex >= currentPlaylist.length) {
    currentVideoIndex = 0
    logMessage("Reached end of playlist. Looping back to start.")
  }
  playNextVideoInPlaylist()
})

videoPlayer.addEventListener("error", (e) => {
  if (!isHandlingError) {
    logMessage("Video playback error occurred", "error")
    if (videoPlayer.error) {
      logMessage(`Error code: ${videoPlayer.error.code}, message: ${videoPlayer.error.message}`, "error")
    }
    handleVideoError()
  }
})

// Handle video loading states
videoPlayer.addEventListener("loadstart", () => {
  logMessage("Video loading started")
})

videoPlayer.addEventListener("canplay", () => {
  logMessage("Video can start playing")
})

videoPlayer.addEventListener("waiting", () => {
  logMessage("Video is waiting for data")
})

videoPlayer.addEventListener("playing", () => {
  logMessage("Video is now playing")
  document.getElementById("loadingIndicator").style.display = "none"
  document.getElementById("errorMessage").style.display = "none"
  isHandlingError = false
})

// Fullscreen functionality
document.addEventListener("click", () => {
  if (!document.fullscreenElement) {
    if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen()
    } else if (document.documentElement.mozRequestFullScreen) {
      document.documentElement.mozRequestFullScreen()
    } else if (document.documentElement.webkitRequestFullscreen) {
      document.documentElement.webkitRequestFullscreen()
    } else if (document.documentElement.msRequestFullscreen) {
      document.documentElement.msRequestFullscreen()
    }
  }
})

// Initialize player
logMessage("Initializing multimedia player...")
logMessage("Press 'D' key to toggle debug mode")
updatePlayer()

// Set up periodic updates
setInterval(updatePlayer, FETCH_INTERVAL)

// Handle page visibility changes
document.addEventListener("visibilitychange", () => {
  if (document.hidden) {
    logMessage("Page hidden, pausing updates")
  } else {
    logMessage("Page visible, resuming updates")
    updatePlayer()
  }
})
