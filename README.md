MathMaze - Ready to Deploy package
=================================
This package includes:
  - node_api/        : Node.js API (Express) for Roblox to call
  - php_dashboard/   : PHP teacher dashboard (teacher login + progress view)
  - mysql_schema/    : SQL schema to create required tables

Quick steps to deploy:
  1. Provision or use a hosted MySQL (db4free.net or your provider).
  2. Import mysql_schema/mathmaze_schema.sql into your DB.
  3. Deploy node_api to a Node host (Render, Railway, VPS). Set env vars for DB.
  4. Deploy php_dashboard to a PHP host and set DB connection in db.php or env vars.
  5. Update your Roblox HttpService URLs to point to the deployed API endpoints.

Important security notes included in each README file. Read them carefully.
