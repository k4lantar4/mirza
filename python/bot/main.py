import asyncio
import os
from aiogram import Bot, Dispatcher
from aiogram.filters import CommandStart
from aiogram.types import Message
import httpx

BOT_TOKEN = os.getenv("BOT_TOKEN", "")
API_BASE_URL = os.getenv("API_BASE_URL", "http://localhost:8000")


async def on_start(message: Message) -> None:
    async with httpx.AsyncClient() as client:
        try:
            r = await client.get(f"{API_BASE_URL}/healthz", timeout=5)
            status = r.json().get("status", "unknown")
        except Exception:
            status = "unreachable"
    await message.answer(f"MoonVPN Python API status: {status}")


def main() -> None:
    if not BOT_TOKEN:
        raise RuntimeError("BOT_TOKEN environment variable is not set")
    dp = Dispatcher()
    dp.message.register(on_start, CommandStart())
    bot = Bot(BOT_TOKEN)
    asyncio.run(dp.start_polling(bot))


if __name__ == "__main__":
    main()
