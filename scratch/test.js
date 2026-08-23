const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  
  await page.goto('http://localhost:8080/admin/index.php');
  
  await page.type('input[type="text"]', 'admin');
  await page.type('input[type="password"]', 'asianindo123');
  await page.click('button[type="submit"]');
  await new Promise(r => setTimeout(r, 2000));
  
  await page.evaluate(() => {
     adminApp().changeView('articles'); // wait, adminApp() is a factory. The instance is bound to x-data.
     document.querySelector('a[href="#"]:nth-child(4)').click(); // fallback
  });
  
  await new Promise(r => setTimeout(r, 2000));
  
  const html = await page.evaluate(() => document.querySelector('div[x-show="currentView===\\\'articles\\\'"]').innerHTML);
  console.log('ARTICLES HTML:', html);
  
  await browser.close();
})();
