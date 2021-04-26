const jsdom = require('jsdom');
const async = require('async');
const puppeteer = require('puppeteer');
const { JSDOM } = jsdom;


const total = Math.max(0, 1000);
const concurrency = Math.min(total, 100);
const targetPage = `https://adapt.k8.libretexts.org/`

JSD().then();
// pup().then();

async function JSD () {
  
  console.time('JSD');
  let completed = 0;
  await async.timesLimit(total, concurrency, async (i)=>{
    const dom = await JSDOM.fromURL(targetPage, {
      resources: "usable",
    });
    // console.log(i);
    process.stdout.write(`${++completed}\r`);
  });
  console.timeEnd('JSD');
}

async function pup(){
  const browser = await puppeteer.launch({
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox'
    ],
    // headless: false,
    // devtools: true,
  });
  console.time('Puppeteer');
  let completed = 0;
  await async.timesLimit(total, concurrency, async (i)=>{
    const page = await browser.newPage();
    await page.goto(targetPage, {
      timeout: 50000,
      waitUntil: ["load", "domcontentloaded", 'networkidle0']
    });
    // console.log(i);
    process.stdout.write(`${++completed}\r`);
  
    if (!page.isClosed())
      await page.close();
  });
  console.timeEnd('Puppeteer');
}
