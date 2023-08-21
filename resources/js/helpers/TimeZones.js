import axios from 'axios'

export async function getTimeZones () {
  try {
    const { data } = await axios.get('/api/time-zones')
    if (data.type !== 'success') {
      alert(data.message)
    }
    return data.time_zones
  } catch (error) {
    alert(error.message)
  }
}
