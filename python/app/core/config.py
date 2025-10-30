from pydantic import BaseModel
import os


class Settings(BaseModel):
    app_name: str = "MoonVPN API"
    database_url: str = os.getenv("DATABASE_URL", "postgresql+psycopg://postgres:postgres@localhost:5432/mirza")
    redis_url: str = os.getenv("REDIS_URL", "redis://localhost:6379/0")
    environment: str = os.getenv("ENVIRONMENT", "dev")


settings = Settings()


