const videoPlayer = document.getElementById("videoPlayer")
const runningText = document.getElementById("runningText")

let currentPlaylist = []
let currentVideoIndex = 0
let currentBannerText = null
let lastFetchTime = 0
const FETCH_INTERVAL = 10000 // Fetch new playlist/banner every 10 seconds

async function fetchCurrentVideoPlaylist() {
  try {
    // Changed action name to match PHP
    const response = await fetch("index.php?action=getCurrentVideoPlaylist")
    const data = await response.json()
    if (data.error) {
      console.error("Error fetching video playlist:", data.error)
      return []
    }
    return data.playlist || []
  } catch (error) {
    console.error("Failed to fetch current video playlist:", error)
    return []
  }
}

async function fetchCurrentBanner() {
  try {
    const response = await fetch("index.php?action=getCurrentBanner")
    const data = await response.json()
    if (data.error) {
      console.error("Error fetching banner:", data.error)
      return null
    }
    return data.bannerText
  } catch (error) {
    console.error("Failed to fetch current banner:", error)
    return null
  }
}

async function updatePlayer() {
  const now = Date.now()
  // Only fetch new data if enough time has passed since last fetch
  if (now - lastFetchTime > FETCH_INTERVAL) {
    const newPlaylist = await fetchCurrentVideoPlaylist()
    const newBannerText = await fetchCurrentBanner()

    if (JSON.stringify(newPlaylist) !== JSON.stringify(currentPlaylist)) {
      console.log("Playlist changed:", newPlaylist)
      currentPlaylist = newPlaylist
      currentVideoIndex = 0 // Reset index when playlist changes
      playNextVideoInPlaylist()
    } else if (currentPlaylist.length > 0 && videoPlayer.paused) {
      videoPlayer.play().catch((e) => console.error("Error resuming video:", e))
    } else if (currentPlaylist.length === 0 && videoPlayer.src) {
      videoPlayer.pause()
      videoPlayer.src = ""
      videoPlayer.load()
      console.log("No video scheduled. Pausing player.")
    }

    if (newBannerText !== currentBannerText) {
      console.log("Changing banner to:", newBannerText)
      runningText.textContent = newBannerText || "No banner scheduled."
      currentBannerText = newBannerText

      runningText.style.animation = "none"
      void runningText.offsetWidth // Trigger reflow
      runningText.style.animation = null
    }
    lastFetchTime = now
  }
}

function playNextVideoInPlaylist() {
  if (currentPlaylist.length === 0) {
    videoPlayer.pause()
    videoPlayer.src = ""
    videoPlayer.load()
    console.log("Playlist is empty. No video to play.")
    // Optionally display a message on screen
    runningText.textContent = "No video scheduled for this time."
    return
  }

  const nextVideoPath = currentPlaylist[currentVideoIndex]
  if (videoPlayer.src !== nextVideoPath) {
    console.log("Playing video:", nextVideoPath)
    videoPlayer.src = nextVideoPath
    videoPlayer.load()
    videoPlayer.play().catch((e) => console.error("Error playing video:", e))
  } else {
    if (videoPlayer.paused) {
      videoPlayer.play().catch((e) => console.error("Error resuming video:", e))
    }
  }
}

videoPlayer.addEventListener("ended", () => {
  console.log("Video ended. Moving to next in playlist.")
  currentVideoIndex++
  if (currentVideoIndex >= currentPlaylist.length) {
    currentVideoIndex = 0 // Loop back to the beginning
    console.log("Reached end of playlist. Looping back to start.")
  }
  playNextVideoInPlaylist()
})

// Handle errors during video loading
videoPlayer.addEventListener("error", (e) => {
  console.error("Video playback error:", e)
  runningText.textContent = "Error playing video. Trying next..."
  currentVideoIndex++
  if (currentVideoIndex >= currentPlaylist.length) {
    currentVideoIndex = 0
  }
  setTimeout(playNextVideoInPlaylist, 2000) 
})

updatePlayer()

setInterval(updatePlayer, FETCH_INTERVAL)

document.addEventListener("click", () => {
  if (document.documentElement.requestFullscreen) {
    document.documentElement.requestFullscreen()
  } else if (document.documentElement.mozRequestFullScreen) {
    document.documentElement.mozRequestFullScreen()
  } else if (document.documentElement.webkitRequestFullscreen) {
    document.documentElement.webkitRequestFullscreen()
  } else if (document.documentElement.msRequestFullscreen) {
    document.documentElement.msRequestFullscreen()
  }
})
