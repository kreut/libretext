const fetch = require('node-fetch')
const jsdom = require('jsdom')
const async = require('async')
const puppeteer = require('puppeteer')
const secrets = require('./auth.json')
const { JSDOM } = jsdom

// const targetPages = [`https://adapt.kdev.libretexts.org/`,
//   'https://adapt.kdev.libretexts.org/students/courses/45/assignments',//Assignments page:
//   'https://adapt.kdev.libretexts.org/students/assignments/392/summary', //Assignment Summary:
//   'https://adapt.kdev.libretexts.org/assignments/392/questions/view', //View assessments:
//   'https://adapt.kdev.libretexts.org/assignments/392/questions/view/98770', //Page with files from Query:
//   'https://adapt.kdev.libretexts.org/assignments/392/questions/view/98760'] //Webwork question:

// const targetPages = [
//   'https://adapt.kdev.libretexts.org/api/assignments/392/questions/view',
  // 'https://adapt.kdev.libretexts.org/api/assignments/392/view-questions-info',
  // 'https://adapt.kdev.libretexts.org/api/scores/45/get-course-scores-by-user',
  // 'https://adapt.kdev.libretexts.org/api/scores/summary/392/98760'
// ]
const targetPages = [
  // 'https://adapt.kdev.libretexts.org/api/assignments/392/questions/view',
  // 'https://adapt.kdev.libretexts.org/api/assignments/392/view-questions-info',
  'https://adapt.kdev.libretexts.org/api/assignments/543/questions/view',
  'https://adapt.kdev.libretexts.org/api/assignments/543/view-questions-info',
]
const DRIVER = 'requests'
// const DRIVER = 'JSD'
// const DRIVER = "pup"

main().then()

async function main () {
  let total = Math.max(0, 1000)
  let concurrency
  console.log(DRIVER)
  
  for (let page of targetPages) {
    console.log(page)
    switch (DRIVER) {
      case 'requests':
        concurrency = Math.min(total, 100)
        await fetchAPI(page, total, concurrency)
        break
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
    await sleep(10000)
  }
}

async function fetchAPI (targetPage, total, concurrency) {
  console.time('requests')
  let completed = 0
  await async.timesLimit(total, concurrency, async (i) => {
    let response = await fetch(targetPage, {
      headers: { cookie: `token=${secrets.kdev}` }
    })
    if (!response.ok) {
      console.error(await response.text())
    }
    process.stdout.write(`${++completed}\r`)
  })
  console.timeEnd('requests')
}

async function JSD (targetPage, total, concurrency) {
  const cookieJar = new jsdom.CookieJar()
  cookieJar.setCookie(`token=${secrets.kdev}`, 'https://adapt.kdev.libretexts.org')
  
  console.time('JSD')
  let completed = 0
  await async.timesLimit(total, concurrency, async (i) => {
    const virtualConsole = new jsdom.VirtualConsole()
    const dom = await JSDOM.fromURL(targetPage, {
      resources: 'usable',
      runScripts: 'dangerously',
      cookieJar,
      virtualConsole
    })
    console.log(dom.serialize())
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
    // console.log(secrets.kdev)
    // await page.setExtraHTTPHeaders({"Authorization": `Bearer ${secrets.kdev}`)
    await page.setCookie({ name: 'token', domain: 'adapt.kdev.libretexts.org', value: secrets.kdev })
    await page.goto(targetPage, {
      timeout: 50000,
      waitUntil: ['load', 'domcontentloaded', 'networkidle0']
    })
    process.stdout.write(`${++completed}\r`)
    
    if (!page.isClosed()) {
      await page.close()
    }
  })
  console.timeEnd('Puppeteer')
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}
