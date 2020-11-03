import axios from 'axios'

let stats = require("stats-lite")

function round(num, precision) {
  num = parseFloat(num)
  if (!precision) return num
  return (Math.round(num / precision) * precision)
}

export async function getScoresSummary(id, url) {
  const {data} = await axios.get(url)
  console.log(data)
  this.scores = data.scores.map(user => parseFloat(user.score))
  console.log(this.scores)
  this.max = Math.max(...this.scores) //https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/max
  this.min = Math.min(...this.scores)
  this.mean = Math.round(stats.mean(this.scores) * 100) / 100
  this.stdev = Math.round(stats.stdev(this.scores) * 100) / 100
  this.range = this.max - this.min
  let precision
  if (this.max < 20) {
    precision = 1
  } else if (this.max < 50) {
    precision = 5
  } else {
    precision = 10
  }

  let labels = []
  let counts = []
  for (let i = 0; i < this.scores.length; i++) {
    let score = round(this.scores[i], precision)
    if (!labels.includes(score)) {
      labels.push(score)
      counts.push(0)
    }
  }
  console.log(counts)

  labels = labels.sort((a, b) => a - b)
  console.log(labels)
  for (let i = 0; i < this.scores.length; i++) {
    for (let j = 0; j < labels.length; j++) {
      let score = round(this.scores[i], precision)
      if (parseFloat(score) === parseFloat(labels[j])) {
        counts[j]++
        break
      }
    }
  }

  return {
    labels: labels,
    datasets: [
      {
        backgroundColor: 'green',
        data: counts
      }
    ]
  }
}
