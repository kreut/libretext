const jsdom = require('jsdom')
const async = require('async')
const puppeteer = require('puppeteer')
const { JSDOM } = jsdom

const targetPages = [`https://adapt.k8.libretexts.org/`,
  'https://adapt.k8.libretexts.org/students/courses/45/assignments',//Assignments page:
  'https://adapt.k8.libretexts.org/students/assignments/392/summary', //Assignment Summary:
  'https://adapt.k8.libretexts.org/assignments/392/questions/view', //View assessments:
  'https://adapt.k8.libretexts.org/assignments/392/questions/view/98770', //Page with files from Query:
  'https://adapt.k8.libretexts.org/assignments/392/questions/view/98760'] //Webwork question:
const DRIVER = 'JSD'
// const DRIVER = "pup"

main().then()

async function main () {
  let total = Math.max(0, 1000)
  let concurrency
  
  for (let page of targetPages) {
    console.log(page)
    switch (DRIVER) {
      case 'JSD':
        concurrency = Math.min(total, 100)
        await JSD(page, total, concurrency)
        break
      case 'pup':
        concurrency = Math.min(total, 10)
        await pup(page, total, concurrency)
        break
      default:
        throw Error(`Invalid Drive ${DRIVER}`)
    }
  }
}

async function JSD (targetPage, total, concurrency) {
  
  console.time('JSD')
  let completed = 0
  await async.timesLimit(total, concurrency, async (i) => {
    const dom = await JSDOM.fromURL(targetPage, {
      resources: 'usable',
    })
    // console.log(i)
    process.stdout.write(`${++completed}\r`)
  })
  console.timeEnd('JSD')
}

async function pup (targetPage, total, concurrency) {
  const browser = await puppeteer.launch({
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox'
    ],
    // headless: false,
    // devtools: true,
  })
  console.time('Puppeteer')
  let completed = 0
  await async.timesLimit(total, concurrency, async (i) => {
    const page = await browser.newPage()
    await page.goto(targetPage, {
      timeout: 50000,
      waitUntil: ['load', 'domcontentloaded', 'networkidle0']
    })
    // console.log(i)
    process.stdout.write(`${++completed}\r`)
    
    if (!page.isClosed()) {
      await page.close()
    }
  })
  console.timeEnd('Puppeteer')
}
