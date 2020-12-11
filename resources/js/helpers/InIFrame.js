export function inIFrame () {
  try {
    return window.self !== window.top
  } catch (e) {
    return true
  }
}
