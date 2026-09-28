/* Run with Node.js and Playwright. No requests reach tracking or logging services. */
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const {chromium}=require(process.env.N24_PLAYWRIGHT_MODULE||'playwright');
const root=path.resolve(__dirname,'..');
const key='n24_consent_manager_consent';
const service=(id)=>({id,name:id,provider:'Test',purpose:'Isolated test',privacyUrl:'/privacy/',cookies:[],embedCode:`<script>window.loaded=window.loaded||{};window.loaded['${id}']=true;</script>`});
const settings={bannerVersion:'test-1',privacyPolicyVersion:'privacy-1',consentLogEnabled:true,consentLogEndpoint:'/log',services:{necessary:[],statistics:[service('stats')],marketing:[],external_media:[service('media_a'),service('media_b')]}};
const valid=(extra={})=>({necessary:true,statistics:false,marketing:false,external_media:false,services:{},history:[],uid:'test-user',timestamp:new Date().toISOString(),bannerVersion:'test-1',privacyPolicyVersion:'privacy-1',...extra});
(async()=>{
 const browser=await chromium.launch({headless:true,...(process.env.N24_BROWSER_PATH?{executablePath:process.env.N24_BROWSER_PATH}:{})});
 let checks=0;
 const open=async({stored,blocked=false,necessaryOnly=false}={})=>{
  const context=await browser.newContext({reducedMotion:'reduce'}),page=await context.newPage(),errors=[],logs=[];
  page.setDefaultTimeout(8000);
  page.on('pageerror',e=>{errors.push(e.message);console.error('Browser error:',e.message);});
  await page.route('http://consent.test/**',async route=>{
   const url=new URL(route.request().url());
   if(url.pathname==='/manager.js')return route.fulfill({contentType:'text/javascript',body:fs.readFileSync(path.join(root,'assets/js/n24-consent-manager.js'))});
   if(url.pathname==='/manager.css')return route.fulfill({contentType:'text/css',body:fs.readFileSync(path.join(root,'assets/css/n24-consent-manager.css'))});
   if(url.pathname==='/log'){logs.push(route.request().postDataJSON());return route.fulfill({status:201,json:{success:true}});}
   const config=necessaryOnly?{...settings,necessaryOnlyMode:true,services:{necessary:[],statistics:[],marketing:[],external_media:[]}}:settings;
   return route.fulfill({contentType:'text/html',body:`<!doctype html><html><head><link rel="stylesheet" href="/manager.css"></head><body><button id="outside">Outside</button><button class="tab-btn active" id="foreign-tab">Other tabs</button><div class="consent-view active" id="foreign-view">Unrelated content</div><div data-n24-content-blocker="vimeo" data-n24-service-id="media_a" data-n24-service-category="external_media"><button class="n24-content-blocker-accept">Immer laden</button></div><script>window.N24ConsentManagerSettings=${JSON.stringify(config).replace(/</g, '\\u003c')};</script><script src="/manager.js"></script></body></html>`});
  });
  await page.goto('http://consent.test/',{waitUntil:'networkidle'});
  if(stored!==undefined||blocked){
   await page.evaluate(({key,stored})=>{localStorage.clear();if(stored!==undefined)localStorage.setItem(key,stored);},{key,stored});
   if(blocked)await page.addInitScript(()=>{for(const method of ['getItem','setItem','removeItem'])Storage.prototype[method]=()=>{throw new DOMException('Blocked','SecurityError');};});
   errors.length=0;await page.reload({waitUntil:'networkidle'});
  }
  return {context,page,errors,logs,close:async()=>{assert.deepEqual(errors,[]);await context.close();checks++;}};
 };
 try{
  for(const stored of ['null','{broken',JSON.stringify(valid({history:{invalid:true}})),JSON.stringify(valid({timestamp:'2999-01-01T00:00:00.000Z'})),JSON.stringify(valid({bannerVersion:'old'}))]){
   const t=await open({stored});if(stored.includes('invalid'))await t.page.locator('#consent-floating-btn').click();
   await t.page.waitForSelector('#consent-banner.visible');await t.page.locator('#consent-reject').click();await t.page.waitForSelector('#consent-banner',{state:'detached'});await t.close();
  }
  const blocked=await open({blocked:true});await blocked.page.locator('#consent-reject').click();await blocked.page.waitForSelector('#consent-banner',{state:'detached'});assert.equal(await blocked.page.evaluate(()=>N24ConsentManager.consent.statistics),false);await blocked.close();
  const reject=await open();assert.equal(await reject.page.evaluate(()=>!!window.loaded),false);await reject.page.locator('#consent-reject').click();await reject.page.waitForSelector('#consent-banner',{state:'detached'});const denied=await reject.page.evaluate(k=>JSON.parse(localStorage.getItem(k)),key);assert.ok(Object.values(denied.services).every(v=>v===false));await reject.page.reload({waitUntil:'networkidle'});assert.equal(await reject.page.locator('#consent-banner').count(),0);assert.equal(await reject.page.evaluate(()=>!!window.loaded),false);await reject.close();
  const accept=await open();await accept.page.locator('#consent-accept-all').click();await accept.page.waitForSelector('#consent-banner',{state:'detached'});assert.deepEqual(await accept.page.evaluate(()=>window.loaded),{stats:true,media_a:true,media_b:true});await accept.page.locator('#consent-floating-btn').click();await accept.page.locator('#consent-reject').click();await accept.page.waitForFunction(()=>!window.loaded && window.N24ConsentManager?.consent.timestamp);assert.equal(await accept.page.evaluate(()=>N24ConsentManager.consent.statistics),false);await accept.close();
  const custom=await open();await custom.page.locator('label:has(#consent-stats)').click();await custom.page.locator('#consent-save').click();await custom.page.waitForSelector('#consent-banner',{state:'detached'});assert.deepEqual(await custom.page.evaluate(()=>window.loaded),{stats:true});await custom.close();
  const one=await open();await one.page.evaluate(()=>N24ConsentManager.hideBanner());await one.page.waitForSelector('#consent-banner',{state:'detached'});await one.page.getByRole('button',{name:'Immer laden',exact:true}).click();let granted=await one.page.evaluate(k=>JSON.parse(localStorage.getItem(k)),key);assert.equal(granted.bannerVersion,'test-1');assert.deepEqual(granted.services,{stats:false,media_a:true,media_b:false});assert.deepEqual(await one.page.evaluate(()=>window.loaded),{media_a:true});await one.page.reload({waitUntil:'networkidle'});assert.equal(await one.page.locator('#consent-banner').count(),0);assert.deepEqual(await one.page.evaluate(()=>window.loaded),{media_a:true});await one.close();
  const unknown=await open({stored:JSON.stringify(valid({statistics:true,services:{old_service:true},history:[]}))});assert.equal(await unknown.page.evaluate(()=>!!window.loaded),false);await unknown.close();
  const keyboard=await open();await keyboard.page.locator('#consent-banner').focus();await keyboard.page.keyboard.press('Shift+Tab');assert.equal(await keyboard.page.evaluate(()=>document.activeElement.id),'consent-customize');await keyboard.page.keyboard.press('Tab');assert.equal(await keyboard.page.evaluate(()=>document.activeElement.dataset.tab),'simple');await keyboard.page.getByRole('button',{name:'Details & Cookies',exact:true}).click();assert.equal(await keyboard.page.locator('#consent-customize').isVisible(),false);assert.ok(await keyboard.page.locator('#foreign-tab').evaluate(e=>e.classList.contains('active')));assert.ok(await keyboard.page.locator('#foreign-view').evaluate(e=>e.classList.contains('active')));await keyboard.page.locator('#consent-banner').focus();await keyboard.page.keyboard.press('Escape');await keyboard.page.waitForSelector('#consent-banner',{state:'detached'});await keyboard.close();
  const necessary=await open({necessaryOnly:true});assert.equal(await necessary.page.locator('#consent-banner').count(),0);await necessary.page.locator('#consent-floating-btn').click();await necessary.page.locator('#consent-close-information').click();await necessary.page.waitForSelector('#consent-banner',{state:'detached'});assert.equal(await necessary.page.evaluate(k=>localStorage.getItem(k),key),null);assert.equal(necessary.logs.length,0);await necessary.close();
  console.log(`PASS: ${checks} browser scenarios (storage, corruption, expiry/version, reject/accept/custom, single service, revocation, focus, necessary-only).`);
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
