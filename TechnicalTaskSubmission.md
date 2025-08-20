# Fruits & Vegetables — Technical Task Submission

## 1. Requirements Coverage

Based on the assignment goals, here is what I implemented:

- **Process `request.json` and create two separate collections (Fruits & Vegetables)**  
  ✔ Implemented domain models (`Food`, `FoodType`, `Unit`) and collection classes (`FruitCollection`, `VegetableCollection`).  
  ✔ Added a console command to seed data from `request.json` into Redis.

- **Collections expose `add()`, `remove()`, `list()`**  
  ✔ Exposed in the domain layer and wired through the `FoodService`. 
  ✔ Search and list both combined in same end point.  

- **Store units in grams**  
  ✔ Canonical unit is **grams** with conversions available via the `Unit` class.

- **Storage engine of choice**  
  ✔ Implemented a `RedisFoodRepository` and integrated Redis via Docker Compose.

- **Provide an API to query collections (filters supported)**  
  ✔ `FoodController` exposes endpoints to query foods; request DTOs validate input and enable filtering.  
  ✔ Bonus: supports returning results in either grams or kilograms.

- **Provide an API to add items**  
  ✔ Implemented with validation via `FoodAddRequestInputType`.

- **Bonus items**  
  ✔ Return units configurable (`g` or `kg`).  
  ✔ Search/filter supported in the service layer.  
  ✔ Latest Symfony version (7.3) and PHP 8.2+ used.


## 2. Step‑by‑Step Guide (Docker compose)

### Clone the repository
```bash
git clone https://github.com/tofaruk/fruits-and-vegetables.git
cd fruits-and-vegetables
```

### Build and run the containers
```bash
docker compose up --build
```

This starts two services:
- **app** (Symfony running on PHP 8.2, serving on port 8080)
- **redis** (used as the storage backend)

### Run the tests
```bash
docker compose exec app ./vendor/bin/phpunit
```

### Seed initial data
```bash
docker compose exec app php bin/console app:food:seed-json request.json
```

### Access the API
The API is available at:
```
http://localhost:8080/api/food
```

#### Example requests

**1) Add a new food (POST /api/food)**
```bash
curl -X POST http://localhost:8080/api/food   -H 'Content-Type: application/json'   -d '{
    "name": "Apple",
    "type": "fruit",
    "quantity": 1.2,
    "unit": "kg"
  }'
```

**2) Query foods (GET /api/food)**
```bash

curl http://localhost:8080/api/food
# Get all foods (default in grams)
curl "http://localhost:8080/api/food?type=fruit&unit=kg&q=pp"
# Get only fruits, with results in kilograms, searching for "pp"
```

**3) Remove a food (DELETE /api/food/{id})**
```bash
# Example: remove the food with ID 5
curl -X DELETE http://localhost:8080/api/food/5
```

---

## 3. Summary

- ✅ All core requirements fully implemented.
- ✅ Most bonus requirements met (unit selection, search, Symfony latest  version).
- ✅ Redis chosen as storage engine, with Dockerized setup for ease of use.
- ✅ Tests, validation, and structured error handling included.
