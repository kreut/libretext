export function roundToDecimalSigFig (num, places = 2) {
  const multiple = 10 ** places
  const rounded = Math.round(num * multiple) / multiple
  return parseFloat(rounded.toString())
}
