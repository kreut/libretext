export const formatDate  = value =>  {
  let months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  let date = new Date(value.replace(' ', 'T'))

  return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear()
}
