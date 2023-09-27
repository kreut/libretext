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
      statusTextClass = 'text-danger'
      break
    case ('Upcoming'):
      statusTextClass = ''
  }
  return statusTextClass
}
