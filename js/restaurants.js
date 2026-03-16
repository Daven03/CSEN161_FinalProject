const includedTags = [];

function normalizeTag(s) {
  return s.trim().toLowerCase();
}

function createTag(text) {
  const btn = document.createElement("button");
  btn.classList.add("tag");
  btn.textContent = text;

  btn.addEventListener("click", () => {
    btn.remove();

    const normalized = normalizeTag(text);
    const index = includedTags.findIndex(
      t => normalizeTag(t) === normalized
    );

    if (index !== -1) {
      includedTags.splice(index, 1);
    }

    hideRestaurants();
  });

  return btn;
}

function hideRestaurants() {
  const restaurants = document.querySelectorAll("main article");

  if (includedTags.length === 0) {
    restaurants.forEach((restaurant) => {
      restaurant.classList.remove("hidden");
    });
    return;
  }

  const includedRestaurants = [];

  restaurants.forEach((restaurant) => {
    const tagEls = restaurant.querySelectorAll(".tag");

    for (const tagEl of tagEls) {
      const tagText = normalizeTag(tagEl.textContent);

      for (const term of includedTags) {
        const termText = normalizeTag(term);

        if (tagText.includes(termText)) {
          includedRestaurants.push(restaurant);
          return;
        }
      }
    }
  });

  restaurants.forEach((restaurant) => {
    if (includedRestaurants.includes(restaurant)) {
      restaurant.classList.remove("hidden");
    } else {
      restaurant.classList.add("hidden");
    }
  });
}

function addSearchTerm(term) {
  const cleaned = term.trim().toLowerCase();

  if (cleaned.length === 0) return;

  const alreadyThere = includedTags.some(
    (t) => normalizeTag(t) === cleaned
  );

  if (alreadyThere) return;

  includedTags.push(cleaned);

  const btn = createTag(cleaned);

  let holder = document.querySelector(".search-tags");
  if (!holder) {
    const header = document.querySelector("header");
    holder = document.createElement("div");
    holder.classList.add("search-tags");
    header.prepend(holder);
  }

  holder.prepend(btn);

  hideRestaurants();
}

function initialize() {
  const params = new URLSearchParams(window.location.search);

  const urlTags = params.getAll("tag")
    .map((t) => t.trim().toLowerCase())
    .filter((t) => t.length > 0);

  console.log(urlTags);

  urlTags.forEach((t) => addSearchTerm(t));
}

document.addEventListener("DOMContentLoaded", () => {
  initialize();

  const input = document.querySelector('input[type="search"]');

  if (input) {
    input.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        addSearchTerm(input.value);
        input.value = "";
      }
    });
  }
});
