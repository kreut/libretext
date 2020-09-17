export function populateTimeZoneSelect(timeZones, vm) {
  let usedTimeZones = []
  for (let i = 0; i < timeZones.length; i++) {
    if (!usedTimeZones.includes(timeZones[i].alternativeName)) {
      if (timeZones[i].name === Intl.DateTimeFormat().resolvedOptions().timeZone) {
        vm.form.time_zone = timeZones[i].name
      }
      vm.timeZones.push({value: timeZones[i].name, text: timeZones[i].alternativeName})
      usedTimeZones.push(timeZones[i].alternativeName)
    }
  }
}
