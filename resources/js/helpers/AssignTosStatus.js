export function getStatusTextClass (status) {
  let statusTextClass = ''
  switch (status) {
    case ('Open'):
      statusTextClass = 'text-success'
      break
    case ('Late'):
      statusTextClass = 'text-warning'
      break
    case ('Closed'):
    case ('Released'):
      statusTextClass = 'dark-red'
      break
    case ('Upcoming'):
      statusTextClass = ''
  }
  return statusTextClass
}
