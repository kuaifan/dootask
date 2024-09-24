// 根据窗口宽度设置广告栏高度
let adBarHeight = window?.innerWidth <= 768 ? 48 : 64;
const adBarHeightPX = `${adBarHeight}px`;

// 当DOM加载完成时执行
document.addEventListener("DOMContentLoaded", function () {
  // 获取当前页面路径
  const currentPath = window.location.pathname;
  // 判断是否为广告页面
  const isAdPage = currentPath.includes("ad.html");

  // 获取URL对象
  const url = new URL(window.location.href);
  // 分割路径
  const pathSegments = url.pathname.split("/");
  // 确定语言
  const language = pathSegments.includes("zh")
    ? "zh"
    : pathSegments.includes("en")
      ? "en"
      : "zh";

  if (isAdPage) {
    // 如果是广告页面，设置导航背景色并获取广告计划和介绍
    setNavBackgroundColor();
    fetchAdBanner(language);
    fetchAdPlan(language);
    fetchAdIntro(language);

    manageAnimate();

    handleDialog();
  } else {
    // 如果不是广告页面，插入广告样式表并获取广告栏
    insertAdStylesheet();
    fetchAdBar(language);
  }
});

// 初始化广告栏
function initializeAdBar() {
  displayAdBar();
  adjustNavPosition("down");

  // 添加关闭广告栏的事件监听器
  const closeAdBarButton = document.getElementById("ad-close");
  closeAdBarButton.addEventListener("click", () => {
    hideAdBar();
    adjustNavPosition("up");
  });
}

// 显示广告栏
function displayAdBar() {
  const adWrapper = document.getElementById("ad");
  if (!adWrapper) return;
  adWrapper.style.height = adBarHeightPX;
  adWrapper.style.display = "block";

  // 调整头部元素的边距
  const headerEl = document.getElementsByTagName("header")[0];
  if (!headerEl) return;
  headerEl.style.marginTop = adBarHeightPX;
}

// 隐藏广告栏
function hideAdBar() {
  const adWrapper = document.getElementById("ad");
  if (!adWrapper) return;
  adWrapper.style.height = "0px";
  adWrapper.style.display = "none";

  // 重置头部元素的边距
  const headerEl = document.getElementsByTagName("header")[0];
  if (!headerEl) return;
  headerEl.style.marginTop = "0px";
}

// 调整导航栏位置
function adjustNavPosition(direction) {
  const navWrapper = document.getElementsByClassName("nav")[0];
  navWrapper.style.top = direction === "down" ? adBarHeightPX : "0";
}

// 获取广告栏数据
function fetchAdBar(language) {
  const apiUrl = `https://cms.hitosea.com/api/doo-task-ad-bar?locale=${language}&populate[0]=background`;
  fetchData(apiUrl)
    .then(({ data: { attributes } }) => {
      updateAdBar(attributes);
    })
    .catch(handleError);
}

// 获取广告banner数据
function fetchAdBanner(language) {
  const query = window.Qs.stringify(
    {
      locale: language,
      populate: {
        title: {
          populate: "*",
        },
        description: {
          populate: "*",
        },
        background: {
          populate: "*",
        },
        underline: {
          populate: "*",
        },
        signUpButton: {
          populate: "*",
        },
        selfHostButton: {
          populate: "*",
        },
        localizations: {
          populate: "*",
        },
      },
    },
    {
      encodeValuesOnly: true,
    }
  );
  const apiUrl = `https://cms.hitosea.com/api/doo-task-ad-banner?${query}`;
  fetchData(apiUrl).then(handleAdBannerResponse).catch(handleError);
}

// 获取广告计划数据
function fetchAdPlan(language) {
  const query = window.Qs.stringify({
    locale: language,
    populate: {
      plans: {
        populate: {
          price: {
            populate: "*",
          },
          button: {
            populate: "*",
          },
          features: {
            populate: "*",
          },
        },
      },
    },
  });
  const apiUrl = `https://cms.hitosea.com/api/doo-task-ad-plan?${query}`;
  fetchData(apiUrl).then(handleAdPlanResponse).catch(handleError);
}

// 获取广告介绍数据
function fetchAdIntro(language) {
  const query = window.Qs.stringify({
    locale: language,
    populate: {
      intros: {
        populate: {
          bar: {
            populate: "*",
          },
          cover: {
            populate: "*",
          },
          title: {
            populate: "*",
          },
          description: {
            populate: "*",
          },
        },
      },
    },
  });
  const apiUrl = `https://cms.hitosea.com/api/doo-task-ad-intro?${query}`;
  fetchData(apiUrl).then(handleAdIntroResponse).catch(handleError);
}

// 通用数据获取函数
function fetchData(url) {
  return fetch(url).then((response) => response.json());
}

function getMediaUrl(media) {
  if (!media?.data?.attributes?.url) {
    return "";
  }
  return `https://cms.hitosea.com${media.data.attributes.url}`;
}

function getStyle(style) {
  if (!style) return null;
  return Object.keys(style)
    .map((key) => `${key}: ${style[key]}`)
    .join("; ");
}

// 处理广告banner响应
function handleAdBannerResponse(response) {
  // 在此实现广告banner处理逻辑
  try {
    const {
      data: {
        attributes: {
          title,
          description,
          background,
          underline,
          signUpButton,
          selfHostButton,
        },
      },
    } = response;
    handleAdBannerTitle(title);
    handleAdBannerDescription(description);
    handleAdBannerBackground(background);
    handleAdBannerUnderline(underline);
    handleAdBannerSignUpButton(signUpButton);
    handleAdBannerSelfHostButton(selfHostButton);
  } catch (error) {
    console.error("处理广告banner响应时出错:", error);
  }
}

function handleAdBannerTitle(title) {
  const titleText = {};
  if (Array.isArray(title)) {
    title.forEach((item) => {
      titleText[item.key] = { text: item.text, style: item.style };
    });
  }
  const titlePart1El = document.getElementById("ad-banner-title-part1");
  const titlePart2El = document.getElementById("ad-banner-title-part2");
  const titlePart3El = document.getElementById("ad-banner-title-part3");
  if (titlePart1El && titleText["part1"]) {
    titlePart1El.textContent = titleText["part1"].text;
    titlePart1El.style = getStyle(titleText["part1"].style);
  }
  if (titlePart2El && titleText["part2"]) {
    titlePart2El.textContent = titleText["part2"].text;
    titlePart2El.style = getStyle(titleText["part2"].style);
  }
  if (titlePart3El && titleText["part3"]) {
    titlePart3El.textContent = titleText["part3"].text;
    titlePart3El.style = getStyle(titleText["part3"].style);
  }
}

function handleAdBannerDescription(description) {
  const descriptionText = {
    text: description.text,
    style: description.style,
  };
  const descriptionEl = document.getElementById("ad-banner-description");
  if (descriptionEl && descriptionText.text) {
    descriptionEl.textContent = descriptionText.text;
    descriptionEl.style = getStyle(descriptionText.style);
  }
}

function handleAdBannerBackground(background) {
  const backgroundUrl = getMediaUrl(background);
  const adBannerEl = document.getElementById("ad-banner");

  if (adBannerEl && backgroundUrl) {
    adBannerEl.style.backgroundImage = `url(${backgroundUrl})`;
  }
}

function handleAdBannerUnderline(underline) {
  const underlineUrl = getMediaUrl(underline);
  const adBannerTitleUnderlineEl = document.getElementById(
    "ad-banner-title-underline"
  );
  if (adBannerTitleUnderlineEl && underlineUrl) {
    adBannerTitleUnderlineEl.innerHTML = `<img class="arcs ad" src="${underlineUrl}" alt="underline" />`;
  }
}

function handleAdBannerSignUpButton({
  theme,
  style,
  link: { label, href, target, slug },
}) {
  const signUpButtonEl = document.getElementById("ad-banner-sign-up-button");
  if (signUpButtonEl) {
    signUpButtonEl.innerHTML = `
        <a href="${href}" ${target === "_blank" ? 'target="_blank"' : ""} >
          <button class="btn btn-primary">
            ${label}
          </button>
        </a>`;
    signUpButtonEl.style = getStyle(style);
  }
}

function handleAdBannerSelfHostButton({
  theme,
  style,
  link: { label, href, target, slug },
}) {
  const selfHostButtonEl = document.getElementById(
    "ad-banner-self-host-button"
  );
  if (selfHostButtonEl) {
    selfHostButtonEl.innerHTML = `
        <a href="${href}" ${target === "_blank" ? 'target="_blank"' : ""} >
          <button class="btn btn-default">
            ${label}
          </button>
        </a>`;
    selfHostButtonEl.style = getStyle(style);
  }
}

// 处理广告计划响应
function handleAdPlanResponse(response) {
  // 在此实现广告计划处理逻辑
  try {
    const {
      data: {
        attributes: { title, description, plans },
      },
    } = response;
    handleAdPlanTitle(title);
    handleAdPlanDescription(description);
    handleAdPlanPlans(plans);
  } catch (error) {
    console.error("处理广告计划响应时出错:", error);
  }
}

function handleAdPlanTitle(title) {
  const planTitleEl = document.getElementById("ad-plan-title");
  if (planTitleEl && title) {
    planTitleEl.textContent = title;
  }
}

function handleAdPlanDescription(description) {
  const planDescriptionEl = document.getElementById("ad-plan-description");
  if (planDescriptionEl && description) {
    planDescriptionEl.textContent = description;
  }
}

async function handleAdPlanPlans(plans) {
  const planContentEl = document.getElementById("ad-plan-content");
  if (planContentEl && Array.isArray(plans)) {
    const prevPlanItems = planContentEl.querySelectorAll(".plan-item");

    for (const prevPlanItem of prevPlanItems) {
      prevPlanItem.classList.add(
        "animate__animated",
        "animate__backOutDown"
      );
      prevPlanItem.addEventListener(
        "animationend",
        () => {
          prevPlanItem.remove();
        },
        { once: true }
      );
    }

    plans.sort((a, b) => a.priority - b.priority);
    for (const plan of plans) {
      const planItemEl = document.createElement("div");
      planItemEl.className = `plan-item ${plan.activated ? "active" : ""
        }`;
      planItemEl.innerHTML = `
          <div class="plan-item-title">
              <span>${plan.title}</span>
          </div>
          <div class="plan-item-price">
              <span class="plan-item-price-current">
                  ${plan.price.current}
                  <span class="plan-item-price-payment">
                      ${plan.price.payment ?? ""}
                  </span>
              </span>
              <span class="plan-item-price-original ${plan.price.isPrice ? "price" : ""
        }">
                  ${plan.price.original ?? ""}
              </span>
          </div>
          <div class="plan-item-button">
              <a href="${plan.button.href}" ${plan.button.target === "_blank" ? 'target="_blank"' : ""
        }>
                  <button class="btn-primary">
                      ${plan.button.label}
                  </button>
              </a>
          </div>
          <div class="plan-item-description">
              <ul class="plan-item-description-list">
                ${plan.features
          .map((feature) => {
            const iconUrl = feature.icon.data
              ? getMediaUrl(feature.icon)
              : "../img/ad/checked.svg";
            return `
                    <li class="plan-item-description-item">
                      <i class="plan-item-description-item-icon">
                        <img src="${iconUrl}" alt="${feature.title}" />
                      </i>
                      <span class="plan-item-description-item-content ${feature.activated ? "" : "disabled"
              }">
                        ${feature.text}
                      </span>
                    </li>
                  `;
          })
          .join("")}
                </ul>
            </div>
          `;
      planContentEl.appendChild(planItemEl);
      planItemEl.classList.add(
        "animate__animated",
        "animate__backInUp",
        "animate__faster",
        "animate__delay-1s"
      );
      couldAdPlanElAnimate[`${plan.id}`] = false;
      planItemEl.addEventListener(
        "animationend",
        () => {
          planItemEl.classList.remove(
            "animate__backInUp",
            "animate__faster",
            "animate__delay-1s"
          );
          couldAdPlanElAnimate[`${plan.id}`] = true;
        },
        { once: true }
      );

      await new Promise((resolve) => {
        setTimeout(resolve, 150);
      });
    }

    overridePlanButton();
  }
}

// 处理广告介绍响应
function handleAdIntroResponse(response) {
  try {
    const {
      data: {
        attributes: { title, description, intros },
      },
    } = response;
    handleAdIntroTitle(title);
    handleAdIntroDescription(description);
    handleAdIntroIntros(intros);
  } catch (error) {
    console.error("处理广告介绍响应时出错:", error);
  }
}

function handleAdIntroTitle(title) {
  const introTitleEl = document.getElementById("ad-intro-title");
  if (introTitleEl && title) {
    introTitleEl.textContent = title;
  }
}

function handleAdIntroDescription(description) {
  const introDescriptionEl = document.getElementById("ad-intro-description");
  if (introDescriptionEl && description) {
    introDescriptionEl.textContent = description;
  }
}

async function handleAdIntroIntros(intros) {
  const introContentEl = document.getElementById("ad-intro-content");
  if (introContentEl && Array.isArray(intros)) {
    const prevIntroItems =
      introContentEl.querySelectorAll(".ad-intro-item");
    for (const prevIntroItem of prevIntroItems) {
      prevIntroItem.classList.add(
        "animate__animated",
        "animate__zoomOut"
      );
      prevIntroItem.addEventListener(
        "animationend",
        () => {
          prevIntroItem.remove();
        },
        { once: true }
      );
    }

    intros.sort((a, b) => a.priority - b.priority);

    for (const intro of intros) {
      const introItemEl = document.createElement("div");
      const barUrl = intro.bar.data
        ? getMediaUrl(intro.bar)
        : "../img/ad/intro-card-head.png";
      const coverUrl = intro.cover.data
        ? getMediaUrl(intro.cover)
        : `../img/ad/intro-card-img${intro.priority + 1}.svg`;
      introItemEl.className = "ad-intro-item";
      introItemEl.innerHTML = `
          <div class="ad-intro-item-header">
              <img src="${barUrl}" alt="intro-bar" />
          </div>
          <div class="ad-intro-item-image">
              <img src="${coverUrl}" alt="intro-cover" />
          </div>
          <div class="ad-intro-item-title">
              <span>${intro.title}</span>
          </div>
          <div class="ad-intro-item-description">
              <span>${intro.description}</span>
          </div>
        `;
      introContentEl.appendChild(introItemEl);
      introItemEl.classList.add(
        "animate__animated",
        "animate__zoomIn",
        "animate__delay-1s"
      );
      couldAdIntroElAnimate[`${intro.id}`] = false;

      introItemEl.addEventListener(
        "animationend",
        () => {
          introItemEl.classList.remove(
            "animate__zoomIn",
            "animate__delay-1s"
          );
          couldAdIntroElAnimate[`${intro.id}`] = true;
        },
        { once: true }
      );
      await new Promise((resolve) => {
        setTimeout(resolve, 150);
      });
    }
  }
}

// 错误处理函数
function handleError(error) {
  console.error("获取数据时出错:", error);
}

// 插入广告栏元素
function insertAdBarElement() {
  const adBarHTML = `
      <div id="ad" class="ad">
          <div class="ad-content">
              <div class="ad-content-left">
                  <p id="ad-text" class="ad-text">最新活动</p>
                  <button id="ad-btn" class="ad-btn">查看详情</button>
              </div>
              <div id="ad-close" class="ad-close">
                  <img src="../img/price_icon2.svg" alt="关闭" />
              </div>
          </div>
      </div>
    `;
  const headerElement = document.getElementsByTagName("header")[0];
  if (headerElement) {
    headerElement.insertAdjacentHTML("afterbegin", adBarHTML);
  }

  // 添加广告按钮点击事件
  const adButton = document.getElementById("ad-btn");
  if (adButton) {
    adButton.addEventListener("click", () => {
      window.location.href = "ad.html";
    });
  }
}

// 插入广告样式表
function insertAdStylesheet() {
  const adStylesheet = document.createElement("link");
  adStylesheet.rel = "stylesheet";
  adStylesheet.type = "text/css";
  adStylesheet.href = "../css/ad.css";
  document.head.appendChild(adStylesheet);
}

// 更新广告栏内容
function updateAdBar({ background, text, buttonText }) {
  insertAdBarElement();

  const adWrapper = document.getElementById("ad");
  if (!adWrapper) return;

  // 设置背景图片
  const backgroundUrl = background?.data?.attributes?.url;
  if (backgroundUrl) {
    adWrapper.style.backgroundImage = `url(https://cms.hitosea.com${backgroundUrl})`;
  }

  // 更新文本内容
  const textElement = adWrapper.querySelector(".ad-text");
  if (textElement) {
    textElement.textContent = text;
  }

  // 更新按钮文本
  const buttonElement = adWrapper.querySelector(".ad-btn");
  if (buttonElement) {
    buttonElement.textContent = buttonText;
  }

  initializeAdBar();
}

// 设置导航栏背景颜色
function setNavBackgroundColor() {
  const navWrapper = document.getElementsByClassName("nav")[0];
  navWrapper.style.backgroundColor = "#fff";
}

// 管理动画
function manageAnimate() {
  let throttleTimer = null;
  const throttle = (callback, time) => {
    if (throttleTimer) return;
    throttleTimer = true;
    setTimeout(() => {
      callback();
      throttleTimer = false;
    }, time);
  };

  window.addEventListener("scroll", () => {
    throttle(() => {
      detectAdPlanEl();
      detectAdIntroEl();
    }, 200);
  });
}

const couldAdPlanElAnimate = {};
const couldAdIntroElAnimate = {};

function detectAdPlanEl() {
  const adPlanEl = document.querySelector(".ad-plan");
  if (isElementOutOfViewport(adPlanEl)) {
    const els = document.querySelectorAll(".plan-item");
    for (const el of els) {
      couldAdPlanElAnimate[`${el.id}`] = true;
    }
    return;
  }

  const _couldAdPlanElAnimate =
    Object.values(couldAdPlanElAnimate).every(Boolean);
  if (!_couldAdPlanElAnimate) return;
  if (isElementPartiallyInViewport(adPlanEl)) {
    const els = document.querySelectorAll(".plan-item");
    for (const el of els) {
      el.classList.add("animate__flipInX");
      couldAdPlanElAnimate[`${el.id}`] = false;
      el.addEventListener(
        "animationend",
        () => {
          el.classList.remove("animate__flipInX");
        },
        { once: true }
      );
    }
    return;
  }
}

function detectAdIntroEl() {
  const adIntroEl = document.querySelector(".ad-intro");
  if (isElementOutOfViewport(adIntroEl)) {
    const els = document.querySelectorAll(".ad-intro-item");
    for (const el of els) {
      couldAdIntroElAnimate[`${el.id}`] = true;
    }
    return;
  }

  const _couldAdIntroElAnimate = Object.values(couldAdIntroElAnimate).every(
    Boolean
  );
  if (!_couldAdIntroElAnimate) return;
  if (isElementPartiallyInViewport(adIntroEl)) {
    const els = document.querySelectorAll(".ad-intro-item");
    for (const el of els) {
      el.classList.add("animate__zoomIn");
      couldAdIntroElAnimate[`${el.id}`] = false;
      el.addEventListener(
        "animationend",
        () => {
          el.classList.remove("animate__zoomIn");
        },
        { once: true }
      );
    }
    return;
  }
}

function isElementPartiallyInViewport(el) {
  if (!el) return false;
  const rect = el.getBoundingClientRect();
  return (
    rect.top <
    (window.innerHeight || document.documentElement.clientHeight) &&
    rect.bottom > 0 &&
    rect.left <
    (window.innerWidth || document.documentElement.clientWidth) &&
    rect.right > 0
  );
}

function isElementOutOfViewport(el) {
  if (!el) return false;
  const rect = el.getBoundingClientRect();
  return (
    rect.bottom < 0 ||
    rect.top >
    (window.innerHeight || document.documentElement.clientHeight) ||
    rect.right < 0 ||
    rect.left > (window.innerWidth || document.documentElement.clientWidth)
  );
}

function handleDialog() {
  const dialogEl = document.querySelector(".ad-dialog");
  if (!dialogEl) return;

  lockBodyScroll(dialogEl.classList.contains("show"));
  overridePlanButton();

  const dialogBackdropEl = dialogEl.querySelector(".ad-dialog-backdrop");
  if (dialogBackdropEl) {
    dialogBackdropEl.addEventListener("click", () => {
      dialogEl.classList.remove("show");
      lockBodyScroll(false);
      handleDialogAnimate(false)
    });
  }

  const dialogFooterBtnEl = dialogEl.querySelector(".ad-dialog-footer-btn");
  if (dialogFooterBtnEl) {
    dialogFooterBtnEl.addEventListener("click", () => {
      dialogEl.classList.remove("show");
      lockBodyScroll(false);
      handleDialogAnimate(false)
    });
  }
}

function lockBodyScroll(bool) {
  document.body.style.overflowY = bool ? "hidden" : "auto";
}

function overridePlanButton() {
  function showDialog(e) {
    e.preventDefault();
    e.stopPropagation();

    const dialogEl = document.querySelector(".ad-dialog");
    if (!dialogEl) return;
    dialogEl.classList.add("show");
    lockBodyScroll(true);
    handleDialogAnimate(true)
  }

  const planButtonEl = document.querySelectorAll(".plan-item-button");
  planButtonEl.forEach((el) => {
    el.removeEventListener("click", showDialog);

    const aEl = el.querySelector("a");
    if (!aEl.href || aEl.href.includes("#")) {
      aEl.removeAttribute("href");
      el.addEventListener("click", showDialog);
    }
  });
}

function handleDialogAnimate(bool) {
  const dialogEl = document.querySelector(".ad-dialog");
  if (!dialogEl) return;

  const dialogWrapperEl = dialogEl.querySelector(".ad-dialog-wrapper");
  if (!dialogWrapperEl) return;

  if (bool) {
    dialogWrapperEl.classList.add("animate__animated", "animate__bounceIn", "animate__faster");
    dialogWrapperEl.addEventListener("animationend", () => {
      dialogWrapperEl.classList.remove("animate__animated", "animate__bounceIn", "animate__faster")
    })
  } else {
    dialogWrapperEl.classList.remove("animate__animated", "animate__bounceIn", "animate__faster")
  }
}