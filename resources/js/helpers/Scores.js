import axios from 'axios'
import stats from 'stats-lite'

function round (num, precision) {
  num = parseFloat(num)
  if (!precision) return num
  return (Math.round(num / precision) * precision)
}

export async function getScoresSummary (id, url) {
  try {
    console.log(url)
    const { data } = await axios.get(url)
    console.log('data')
    console.log(data)
    if (data.type === 'error') {
      this.$noty.error(data.message)
    }
    if (!data.scores) {
      return false
    }
    this.scores = data.scores.map(score => parseFloat(score))
    this.scores = this.scores.filter(value => !Number.isNaN(value))// in case of nulls....
    console.log(this.scores)
    this.max = Math.max(...this.scores) // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/max
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
      let score = round(parseFloat(this.scores[i]), precision)
      if (!labels.includes(score)) {
        labels.push(score)
        counts.push(0)
      }
    }

    labels = labels.sort((a, b) => a - b)
    console.log(labels)
    for (let i = 0; i < this.scores.length; i++) {
      for (let j = 0; j < labels.length; j++) {
        let score = round(parseFloat(this.scores[i]), precision)
        if (parseFloat(score) === parseFloat(labels[j])) {
          counts[j]++
          break
        }
      }
    }
    let labelsWithCounts = []
    for (let i = 0; i < labels.length; i++) {
      labelsWithCounts[i] = { number_of_students: counts[i], score: labels[i] }
    }
    console.log('counts')
    console.log(counts)

    return {
      labelsWithCounts: labelsWithCounts,
      labels: labels,
      datasets: [
        {
          backgroundColor: 'green',
          data: counts
        }
      ]
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}
