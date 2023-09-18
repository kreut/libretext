function pusherKey () {
  let key = ''
  switch (window.config.environment) {
    case ('local'):
    case ('dev'):
      key = 'ef64f0e4887539d20979'
      break
    case ('staging'):
      key = 'f977128bedb93cf2afb3'
      break
    case ('production'):
      key = '03683cd6b9e521cc83e2'
      break
  }
  if (key === '') {
    alert('Notifications are not being pushed because of invalid key or invalid environment.')
  }
  return key
}

export function initPusher () {
  if (window.config.environment !== 'production') {
    Pusher.logToConsole = true
  }
  return new Pusher(pusherKey(), {
    cluster: 'us3',
    encrypted: true
  })
}
