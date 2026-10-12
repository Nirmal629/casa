# Local Docker setup

Start the website and local MariaDB database:

```powershell
docker compose up --build
```

Open `http://localhost:8080` in a browser. The database is available to desktop database tools at `localhost:3307` with user `casa_test` and password `casa_test123#`.

The database data is stored in the Docker volume `git_mariadb_data`. The SQL dump is imported only when the volume is first created.

To reset the local database and import `casa_test.sql` again (this deletes only the local Docker database volume):

```powershell
docker compose down -v
docker compose up --build
```

Stop the local environment with `docker compose down`.
